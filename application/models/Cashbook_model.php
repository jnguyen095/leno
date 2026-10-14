<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sổ quỹ = phiếu Thu / Chi nhập tay (cashbook_entries) + tiền bán hàng đọc thẳng từ payments (đơn đã thanh toán,
 * hiện thành phiếu thu "Bán hàng" chỉ xem). Danh sách và tổng tiền gộp 2 nguồn bằng UNION.
 */
class Cashbook_model extends CI_Model
{
    const SALE_CATEGORY = 'SALE'; // bộ lọc danh mục "Bán hàng" (từ đơn hàng)

    // ---------- Danh mục thu / chi ----------

    public function get_categories($type = NULL, $only_active = TRUE)
    {
        if ($type) $this->db->where('type', $type);
        if ($only_active) $this->db->where('status', 'ACTIVE');
        return $this->db->order_by('type', 'ASC')->order_by('sort_order', 'ASC')->order_by('name', 'ASC')
            ->get('cashbook_categories')->result_array();
    }

    public function get_category($id)
    {
        return $this->db->where('id', (int) $id)->get('cashbook_categories')->row_array();
    }

    public function save_category($id, $type, $name)
    {
        $now = date('Y-m-d H:i:s');
        if ($id)
        {
            $this->db->where('id', (int) $id)->update('cashbook_categories', array('name' => $name, 'updated_at' => $now));
            return (int) $id;
        }
        $max = (int) $this->db->select_max('sort_order')->where('type', $type)->get('cashbook_categories')->row('sort_order');
        $this->db->insert('cashbook_categories', array('type' => $type, 'name' => $name, 'sort_order' => $max + 10, 'created_at' => $now, 'updated_at' => $now));
        return (int) $this->db->insert_id();
    }

    public function set_category_status($id, $status)
    {
        return $this->db->where('id', (int) $id)->update('cashbook_categories', array('status' => $status, 'updated_at' => date('Y-m-d H:i:s')));
    }

    public function count_entries_in_category($id)
    {
        return (int) $this->db->where('category_id', (int) $id)->count_all_results('cashbook_entries');
    }

    // ---------- Phiếu thu / chi nhập tay ----------

    public function get_entry($id)
    {
        return $this->db->where('id', (int) $id)->get('cashbook_entries')->row_array();
    }

    /** Mã phiếu kế tiếp: PT000001 (thu) / PC000001 (chi). */
    public function next_entry_no($type)
    {
        $prefix = $type === 'IN' ? 'PT' : 'PC';
        $last = $this->db->select('entry_no')->like('entry_no', $prefix, 'after')->order_by('id', 'DESC')->limit(1)
            ->get('cashbook_entries')->row('entry_no');
        $n = $last ? (int) substr($last, 2) + 1 : 1;
        return $prefix.str_pad($n, 6, '0', STR_PAD_LEFT);
    }

    public function create_entry(array $data, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        // Mã phiếu tăng dần theo loại; trùng (2 người lưu cùng lúc) thì lấy mã kế tiếp.
        for ($try = 0; $try < 5; $try++)
        {
            $row = $data + array('entry_no' => $this->next_entry_no($data['type']), 'created_by' => $user_id, 'created_at' => $now, 'updated_at' => $now);
            if ($this->db->insert('cashbook_entries', $row)) return (int) $this->db->insert_id();
        }
        return 0;
    }

    public function update_entry($id, array $data)
    {
        unset($data['type'], $data['entry_no']);
        return $this->db->where('id', (int) $id)->update('cashbook_entries', $data + array('updated_at' => date('Y-m-d H:i:s')));
    }

    public function delete_entry($id)
    {
        return $this->db->where('id', (int) $id)->delete('cashbook_entries');
    }

    /** Tên người nộp / người nhận đã dùng (gợi ý khi gõ), mới dùng trước. */
    public function get_counterparties($limit = 200)
    {
        return array_column($this->db->query(
            "SELECT counterparty, MAX(entry_date) AS last_used FROM cashbook_entries
             WHERE counterparty IS NOT NULL AND counterparty <> '' GROUP BY counterparty ORDER BY last_used DESC LIMIT ".(int) $limit
        )->result_array(), 'counterparty');
    }

    /**
     * Phiếu nhập tay kèm tên loại / nhân viên — cho ứng dụng POS (in phiếu thu, danh sách phiếu đã lập trong ngày).
     * $filters: id, type, created_by, date (Y-m-d, theo entry_date).
     */
    public function get_entries_detailed(array $filters, $limit = 100)
    {
        $this->db->select('ce.*, cc.name AS category_name, hu.fullname AS handled_name')
            ->from('cashbook_entries ce')
            ->join('cashbook_categories cc', 'cc.id = ce.category_id', 'left')
            ->join('users hu', 'hu.id = ce.handled_by', 'left');
        if ( ! empty($filters['id'])) $this->db->where('ce.id', (int) $filters['id']);
        if ( ! empty($filters['type'])) $this->db->where('ce.type', $filters['type']);
        if ( ! empty($filters['created_by'])) $this->db->where('ce.created_by', (int) $filters['created_by']);
        if ( ! empty($filters['date']))
        {
            $this->db->where('ce.entry_date >=', $filters['date'].' 00:00:00')->where('ce.entry_date <=', $filters['date'].' 23:59:59');
        }
        return $this->db->order_by('ce.entry_date', 'DESC')->order_by('ce.id', 'DESC')->limit((int) $limit)->get()->result_array();
    }

    /**
     * Báo cáo cuối ngày: phiếu thu nhân viên này thu (handled_by) trong [$from, $to], theo hình thức:
     * method => array(count, total).
     */
    public function shift_receipts($user_id, $from, $to)
    {
        $rows = $this->db->select('payment_method, COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total', FALSE)
            ->from('cashbook_entries')
            ->where('type', 'IN')
            ->where('handled_by', (int) $user_id)
            ->where('entry_date >=', $from)
            ->where('entry_date <=', $to)
            ->group_by('payment_method')
            ->get()->result_array();
        $out = array();
        foreach ($rows as $r)
        {
            $out[$r['payment_method']] = array('count' => (int) $r['cnt'], 'total' => (float) $r['total']);
        }
        return $out;
    }

    // ---------- Danh sách + tổng (gộp phiếu nhập tay + tiền bán hàng) ----------

    /**
     * SQL gộp 2 nguồn, đã áp bộ lọc. $filters: date_from, date_to (Y-m-d), type (IN|OUT), method (CASH|TRANSFER),
     * category (id | 'SALE'), handled_by (user id). $before: chỉ lấy trước thời điểm này (tính quỹ đầu kỳ), bỏ lọc ngày.
     */
    private function _union_sql(array $filters, $before = NULL)
    {
        $e = function ($v) { return $this->db->escape($v); };
        $manual = array('1=1');
        $sale = array("o.status = 'PAID'");

        if ($before !== NULL)
        {
            $manual[] = 'ce.entry_date < '.$e($before);
            $sale[] = 'p.paid_at < '.$e($before);
        }
        else
        {
            if ( ! empty($filters['date_from'])) { $manual[] = 'ce.entry_date >= '.$e($filters['date_from'].' 00:00:00'); $sale[] = 'p.paid_at >= '.$e($filters['date_from'].' 00:00:00'); }
            if ( ! empty($filters['date_to']))   { $manual[] = 'ce.entry_date <= '.$e($filters['date_to'].' 23:59:59');   $sale[] = 'p.paid_at <= '.$e($filters['date_to'].' 23:59:59'); }
        }
        if ( ! empty($filters['method'])) { $manual[] = 'ce.payment_method = '.$e($filters['method']); $sale[] = 'p.payment_method = '.$e($filters['method']); }
        if ( ! empty($filters['handled_by'])) { $manual[] = 'ce.handled_by = '.(int) $filters['handled_by']; $sale[] = 'p.paid_by = '.(int) $filters['handled_by']; }

        $with_manual = TRUE; $with_sale = TRUE;
        if ( ! empty($filters['type']))
        {
            $manual[] = 'ce.type = '.$e($filters['type']);
            if ($filters['type'] === 'OUT') $with_sale = FALSE;
        }
        if ( ! empty($filters['category']))
        {
            if ($filters['category'] === self::SALE_CATEGORY) $with_manual = FALSE;
            else { $manual[] = 'ce.category_id = '.(int) $filters['category']; $with_sale = FALSE; }
        }

        $parts = array();
        if ($with_manual)
        {
            $parts[] = "SELECT 'MANUAL' AS src, ce.id, ce.entry_no AS code, ce.type, ce.entry_date AS dt, ce.payment_method,
                    ce.category_id, cc.name AS category_name, ce.counterparty, ce.handled_by, hu.fullname AS handled_name,
                    ce.amount, ce.note, NULL AS order_id
                FROM cashbook_entries ce
                LEFT JOIN cashbook_categories cc ON cc.id = ce.category_id
                LEFT JOIN users hu ON hu.id = ce.handled_by
                WHERE ".implode(' AND ', $manual);
        }
        if ($with_sale)
        {
            $parts[] = "SELECT 'SALE' AS src, p.id, o.order_no AS code, 'IN' AS type, p.paid_at AS dt, p.payment_method,
                    NULL AS category_id, 'Bán hàng' AS category_name, IFNULL(t.table_name, 'Mang đi') AS counterparty,
                    p.paid_by AS handled_by, pu.fullname AS handled_name, p.amount, NULL AS note, o.id AS order_id
                FROM payments p
                JOIN order_sessions o ON o.id = p.order_session_id
                LEFT JOIN table_sessions ts ON ts.id = o.table_session_id
                LEFT JOIN cafe_tables t ON t.id = ts.table_id
                LEFT JOIN users pu ON pu.id = p.paid_by
                WHERE ".implode(' AND ', $sale);
        }
        return $parts ? implode(' UNION ALL ', $parts) : NULL;
    }

    public function get_list(array $filters, $limit, $offset)
    {
        $sql = $this->_union_sql($filters);
        if ( ! $sql) return array();
        return $this->db->query("SELECT * FROM ($sql) x ORDER BY x.dt DESC, x.src ASC, x.id DESC LIMIT ".(int) $offset.', '.(int) $limit)->result_array();
    }

    public function count_list(array $filters)
    {
        $sql = $this->_union_sql($filters);
        return $sql ? (int) $this->db->query("SELECT COUNT(*) AS n FROM ($sql) x")->row('n') : 0;
    }

    /**
     * Tổng thu / chi theo hình thức trong kỳ: array('IN' => array('CASH' =>, 'TRANSFER' =>, 'ALL' =>), 'OUT' => ...).
     */
    public function totals(array $filters, $before = NULL)
    {
        $out = array();
        foreach (array('IN', 'OUT') as $t) $out[$t] = array('CASH' => 0.0, 'TRANSFER' => 0.0, 'ALL' => 0.0);
        $sql = $this->_union_sql($filters, $before);
        if ( ! $sql) return $out;
        foreach ($this->db->query("SELECT x.type, x.payment_method, SUM(x.amount) AS total FROM ($sql) x GROUP BY x.type, x.payment_method")->result_array() as $r)
        {
            $out[$r['type']][$r['payment_method']] = (float) $r['total'];
            $out[$r['type']]['ALL'] += (float) $r['total'];
        }
        return $out;
    }

    /**
     * Tồn quỹ đầu kỳ theo hình thức = toàn bộ thu - chi trước ngày bắt đầu (theo cùng bộ lọc hình thức / người,
     * không theo loại / danh mục). array('CASH' =>, 'TRANSFER' =>, 'ALL' =>).
     */
    public function opening_balance(array $filters)
    {
        $res = array('CASH' => 0.0, 'TRANSFER' => 0.0, 'ALL' => 0.0);
        if (empty($filters['date_from'])) return $res;
        $base = array_intersect_key($filters, array_flip(array('method', 'handled_by')));
        $t = $this->totals($base, $filters['date_from'].' 00:00:00');
        foreach (array('CASH', 'TRANSFER', 'ALL') as $m) $res[$m] = $t['IN'][$m] - $t['OUT'][$m];
        return $res;
    }
}
