<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_model extends CI_Model
{
    protected $table = 'order_sessions';

    public function create_for_table_session($table_session_id, $order_type = 'DINE_IN', $created_by = NULL)
    {
        $data = array(
            'order_no'          => gen_no('ORD'),
            'order_type'        => $order_type,
            'table_session_id'  => $table_session_id,
            'status'            => 'OPEN',
            'created_by'        => $created_by,
            'subtotal'          => 0,
            'discount_amount'   => 0,
            'vat_amount'        => 0,
            'total_amount'      => 0,
            'created_at'        => date('Y-m-d H:i:s'),
        );
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Shared "open a table" orchestration — used by staff opening a table
     * from the table map.
     * Closes any stray OPEN sessions first, opens a fresh one and creates its
     * order. The table itself stays AVAILABLE ("Trống") until the first item is
     * added — see sync_table_status(). Returns ['session_id'=>, 'order_id'=>].
     */
    public function open_table_with_order($table_id, $opened_by = NULL)
    {
        $this->load->model(array('Table_model', 'Table_session_model'));

        $this->Table_session_model->close_stray_open_sessions($table_id);
        $session_id = $this->Table_session_model->open($table_id, $opened_by);
        $table = $this->Table_model->get_by_id($table_id);
        $order_id = $this->create_for_table_session($session_id, ( ! empty($table['is_takeaway'])) ? 'TAKEAWAY' : 'DINE_IN', $opened_by);

        return array('session_id' => $session_id, 'order_id' => $order_id);
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_open_by_table_session($table_session_id)
    {
        return $this->db->where('table_session_id', $table_session_id)
            ->where('status', 'OPEN')
            ->order_by('id', 'DESC')
            ->get($this->table)->row_array();
    }

    public function get_active_by_table_session($table_session_id)
    {
        return $this->db->where('table_session_id', $table_session_id)
            ->where_in('status', array('OPEN', 'WAIT_PAYMENT'))
            ->order_by('id', 'DESC')
            ->get($this->table)->row_array();
    }

    public function recalc_totals($order_id)
    {
        $order = $this->get_by_id($order_id);
        if ( ! $order) return;

        $subtotal = (float) $this->db->select_sum('amount')
            ->where('order_session_id', $order_id)
            ->where('status', 'ACTIVE')
            ->get('order_items')->row('amount');

        // Khuyến mãi đơn đang dùng: tính lại theo tạm tính mới (chưa đủ giá trị tối thiểu = 0, đủ lại thì tự áp lại).
        $discount = (float) $order['discount_amount'];
        if ($order['discount_type'] !== NULL)
        {
            $this->load->model('Discount_model');
            $discount = Discount_model::compute($order['discount_type'], $order['discount_value'], $order['discount_max'],
                $order['discount_min_order'], $subtotal);
        }

        // VAT tính trên số tiền sau khuyến mãi.
        $this->load->model('Setting_model');
        $vat = round(max(0, $subtotal - $discount) * $this->Setting_model->get_vat_rate());
        $total = $subtotal - $discount + $vat;

        $this->db->where('id', $order_id)->update($this->table, array(
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'vat_amount'      => $vat,
            'total_amount'    => max(0, $total),
        ));
    }

    /**
     * Khuyến mãi người bán chọn cho đơn ($discount = dòng bảng discounts, NULL = bỏ). Lưu nguyên điều kiện lúc chọn
     * để đơn không đổi khi khuyến mãi bị sửa / xoá; số tiền giảm được tính lại ngay.
     */
    public function set_discount($order_id, $discount)
    {
        $this->db->where('id', $order_id)->update($this->table, array(
            'discount_id'        => $discount ? (int) $discount['id'] : NULL,
            'discount_name'      => $discount ? $discount['name'] : NULL,
            'discount_type'      => $discount ? $discount['discount_type'] : NULL,
            'discount_value'     => $discount ? $discount['value'] : NULL,
            'discount_max'       => $discount ? $discount['max_discount'] : NULL,
            'discount_min_order' => $discount ? $discount['min_order'] : NULL,
            'discount_amount'    => 0,
        ));
        $this->recalc_totals($order_id);
    }

    /** Bảng giá đang áp cho đơn (lưu cả tên để in hóa đơn đúng kể cả khi bảng giá bị sửa / xoá). */
    public function set_price_book($id, $book_id, $book_name)
    {
        return $this->db->where('id', $id)->update($this->table, array('price_book_id' => $book_id, 'price_book_name' => $book_name));
    }

    public function set_note($id, $note)
    {
        return $this->db->where('id', $id)->update($this->table, array('note' => $note));
    }

    public function mark_wait_payment($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'WAIT_PAYMENT'));
    }

    public function mark_paid($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'PAID', 'paid_at' => date('Y-m-d H:i:s')));
    }

    public function cancel($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'CANCELLED'));
    }

    /** Đơn đang phục vụ (OPEN/WAIT_PAYMENT) kèm tên bàn — dùng cho thanh chuyển nhanh giữa các khách ở tab Thực đơn. */
    /**
     * Trạng thái bàn theo món trong đơn: có món -> "Đang phục vụ" (OPEN, hoặc giữ WAIT_PAYMENT),
     * không còn món nào -> "Trống" (AVAILABLE). Phiên/đơn rỗng vẫn mở để lần sau chọn lại bàn
     * dùng tiếp (Tables::open), không tạo đơn mới.
     */
    public function sync_table_status($order_id)
    {
        $order = $this->get_detail($order_id);
        if ( ! $order || ! $order['table_id'] || ! in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE))
        {
            return;
        }
        $has_items = $this->db->where('order_session_id', $order_id)->where('status', 'ACTIVE')->count_all_results('order_items') > 0;
        $status = $has_items ? ($order['status'] === 'WAIT_PAYMENT' ? 'WAIT_PAYMENT' : 'OPEN') : 'AVAILABLE';

        $this->load->model('Table_model');
        $this->Table_model->set_status($order['table_id'], $status);
    }

    public function get_active_orders()
    {
        return $this->db->select('order_sessions.id, order_sessions.order_no, order_sessions.total_amount, order_sessions.status, table_sessions.table_id, cafe_tables.table_name, cafe_tables.is_takeaway,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_session_id = order_sessions.id AND oi.status = \'ACTIVE\') AS item_count', FALSE)
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left')
            ->where_in('order_sessions.status', array('OPEN', 'WAIT_PAYMENT'))
            ->order_by('cafe_tables.is_takeaway', 'DESC')
            ->order_by('cafe_tables.sort_order', 'ASC')
            ->order_by('order_sessions.id', 'ASC')
            ->get()->result_array();
    }

    /**
     * Xoá hẳn một đơn cùng món, phiếu bếp và thanh toán của nó. Đơn còn đang phục vụ thì
     * đóng phiên bàn và trả bàn về "Trống". Trả về TRUE nếu đã xoá.
     */
    public function delete_order($id)
    {
        $order = $this->get_detail($id);
        if ( ! $order) return FALSE;

        $this->db->trans_start();
        $ticket_ids = array_column($this->db->select('id')->where('order_session_id', $id)->get('kitchen_tickets')->result_array(), 'id');
        if ($ticket_ids)
        {
            $this->db->where_in('ticket_id', $ticket_ids)->delete('kitchen_ticket_items');
            $this->db->where_in('id', $ticket_ids)->delete('kitchen_tickets');
        }
        $this->db->where('order_session_id', $id)->delete('payments');
        $this->db->where('order_session_id', $id)->delete('order_items');
        $this->db->where('id', $id)->delete($this->table);

        if ($order['table_session_id'] && in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE))
        {
            $this->load->model(array('Table_session_model', 'Table_model'));
            $this->Table_session_model->close($order['table_session_id']);
            $this->Table_model->set_status($order['table_id'], 'AVAILABLE');
        }
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_detail($id)
    {
        return $this->db->select('order_sessions.*, table_sessions.table_id, cafe_tables.table_name, cafe_tables.table_code, cafe_tables.note AS table_note, creator.fullname AS created_by_name')
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left')
            ->join('users creator', 'creator.id = order_sessions.created_by', 'left')
            ->where('order_sessions.id', $id)
            ->get()->row_array();
    }

    public function get_list($filters = array(), $limit = NULL, $offset = 0)
    {
        $this->db->select('order_sessions.*, cafe_tables.table_name, cafe_tables.table_code, creator.fullname AS created_by_name,
                (SELECT p.payment_method FROM payments p WHERE p.order_session_id = order_sessions.id ORDER BY p.id DESC LIMIT 1) AS payment_method', FALSE)
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left')
            ->join('users creator', 'creator.id = order_sessions.created_by', 'left');

        $this->_apply_list_filters($filters);
        // Đơn vừa đóng (thanh toán) gần nhất lên đầu; đơn đang mở (chưa có giờ đóng) xếp theo giờ mở.
        $this->db->order_by('COALESCE(order_sessions.paid_at, order_sessions.created_at)', 'DESC', FALSE)
            ->order_by('order_sessions.id', 'DESC');

        if ($limit !== NULL)
        {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Tổng hợp theo trạng thái cho cùng bộ lọc (bỏ qua lọc trạng thái): array(status => array('count', 'total')).
     * Dùng cho số đếm trên các tab trạng thái + doanh thu ở trang Đơn hàng.
     */
    public function summary_by_status($filters = array())
    {
        unset($filters['status']);
        $this->db->select('order_sessions.status, COUNT(*) AS cnt, COALESCE(SUM(order_sessions.total_amount), 0) AS total', FALSE)
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left');
        $this->_apply_list_filters($filters);
        $this->db->group_by('order_sessions.status');

        $out = array();
        foreach ($this->db->get()->result_array() as $r)
        {
            $out[$r['status']] = array('count' => (int) $r['cnt'], 'total' => (float) $r['total']);
        }
        return $out;
    }

    public function count_list($filters = array())
    {
        $this->db->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left');

        $this->_apply_list_filters($filters);

        return $this->db->count_all_results();
    }

    private function _apply_list_filters($filters)
    {
        if ( ! empty($filters['status']))
        {
            $this->db->where('order_sessions.status', $filters['status']);
        }
        // Ngày của đơn: đã thanh toán -> ngày thanh toán (doanh thu tính vào ngày đóng đơn, khớp Sổ quỹ);
        // chưa thanh toán / đã huỷ -> ngày mở. Vd mở 13/10, thanh toán 14/10 -> thuộc 14/10.
        if ( ! empty($filters['date']))
        {
            $this->_where_order_day($filters['date'].' 00:00:00', $filters['date'].' 23:59:59');
        }
        if ( ! empty($filters['date_from']) || ! empty($filters['date_to']))
        {
            $this->_where_order_day(
                ! empty($filters['date_from']) ? $filters['date_from'].' 00:00:00' : NULL,
                ! empty($filters['date_to']) ? $filters['date_to'].' 23:59:59' : NULL
            );
        }
        if ( ! empty($filters['table_id']))
        {
            $this->db->where('cafe_tables.id', $filters['table_id']);
        }
        if ( ! empty($filters['created_by']))
        {
            $this->db->where('order_sessions.created_by', (int) $filters['created_by']);
        }
        // Phương thức thanh toán (CASH/TRANSFER, xem payment_methods()) hoặc NONE = đơn chưa có thanh toán.
        if ( ! empty($filters['payment_method']))
        {
            if ($filters['payment_method'] === 'NONE')
            {
                $this->db->where('NOT EXISTS (SELECT 1 FROM payments p WHERE p.order_session_id = order_sessions.id)', NULL, FALSE);
            }
            else
            {
                $this->db->where('EXISTS (SELECT 1 FROM payments p WHERE p.order_session_id = order_sessions.id AND p.payment_method = '.$this->db->escape($filters['payment_method']).')', NULL, FALSE);
            }
        }
    }

    /**
     * Lọc đơn theo "ngày của đơn" trong [$from, $to] (NULL = không giới hạn đầu đó): đơn đã thanh toán theo
     * paid_at, đơn chưa thanh toán / đã huỷ theo created_at. Dùng chung cho danh sách Đơn hàng và báo cáo ca.
     */
    private function _where_order_day($from, $to)
    {
        $this->db->group_start()
            ->group_start()->where('order_sessions.status', 'PAID');
        if ($from !== NULL) $this->db->where('order_sessions.paid_at >=', $from);
        if ($to !== NULL) $this->db->where('order_sessions.paid_at <=', $to);
        $this->db->group_end()
            ->or_group_start()->where('order_sessions.status !=', 'PAID');
        if ($from !== NULL) $this->db->where('order_sessions.created_at >=', $from);
        if ($to !== NULL) $this->db->where('order_sessions.created_at <=', $to);
        $this->db->group_end()
            ->group_end();
    }

    /**
     * Đơn do một nhân viên tạo trong một ngày (giờ tạo mới nhất trước), kèm tên bàn, số món đang gọi và
     * phương thức thanh toán. Bỏ qua đơn không còn món nào (mở bàn rồi bỏ, hoặc đã gộp sang bàn khác).
     */
    public function get_history_for_user($user_id, $date)
    {
        return $this->db->select('order_sessions.id, order_sessions.order_no, order_sessions.order_type, order_sessions.status,
                order_sessions.total_amount, order_sessions.note, order_sessions.created_at, order_sessions.paid_at,
                table_sessions.table_id, cafe_tables.table_name,
                (SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi WHERE oi.order_session_id = order_sessions.id AND oi.status = \'ACTIVE\') AS item_count,
                (SELECT p.payment_method FROM payments p WHERE p.order_session_id = order_sessions.id ORDER BY p.id DESC LIMIT 1) AS payment_method', FALSE)
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left')
            ->where('order_sessions.created_by', (int) $user_id)
            // Đơn đã thanh toán tính theo giờ thanh toán (khớp Sổ quỹ / doanh thu: đơn mở hôm trước, trả hôm nay thì
            // thuộc hôm nay); đơn chưa thanh toán / đã huỷ tính theo giờ mở.
            ->group_start()
                ->group_start()
                    ->where('order_sessions.status', 'PAID')
                    ->where('order_sessions.paid_at >=', $date.' 00:00:00')
                    ->where('order_sessions.paid_at <=', $date.' 23:59:59')
                ->group_end()
                ->or_group_start()
                    ->where('order_sessions.status !=', 'PAID')
                    ->where('order_sessions.created_at >=', $date.' 00:00:00')
                    ->where('order_sessions.created_at <=', $date.' 23:59:59')
                ->group_end()
            ->group_end()
            ->having('item_count >', 0)
            ->order_by('COALESCE(order_sessions.paid_at, order_sessions.created_at)', 'DESC', FALSE)
            ->order_by('order_sessions.id', 'DESC')
            ->get()->result_array();
    }

    /**
     * Báo cáo cuối ngày (ca làm) của một nhân viên: đơn do người này tạo trong [$from, $to] — đếm theo trạng thái.
     * Đơn chưa có món nào (mở bàn rồi để trống) không tính; đơn đã huỷ thì tính dù mọi món đã huỷ.
     * Trả về array(count, paid_count, open_count, open_total, cancelled_count).
     */
    public function shift_order_counts($user_id, $from, $to)
    {
        $this->db->select('order_sessions.status, order_sessions.total_amount,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_session_id = order_sessions.id) AS line_count', FALSE)
            ->from($this->table)
            ->where('order_sessions.created_by', (int) $user_id);
        // Đơn đã thanh toán tính theo giờ thanh toán, đơn khác theo giờ mở (giống danh sách Đơn hàng / lịch sử).
        $this->_where_order_day($from, $to);
        $rows = $this->db->get()->result_array();

        $out = array('count' => 0, 'paid_count' => 0, 'open_count' => 0, 'open_total' => 0.0, 'cancelled_count' => 0);
        foreach ($rows as $r)
        {
            if ((int) $r['line_count'] === 0) continue;
            $out['count']++;
            if ($r['status'] === 'PAID') $out['paid_count']++;
            elseif ($r['status'] === 'CANCELLED') $out['cancelled_count']++;
            else { $out['open_count']++; $out['open_total'] += (float) $r['total_amount']; }
        }
        return $out;
    }

    /**
     * Tiền nhân viên đã thu trong [$from, $to] (thanh toán do người này xác nhận, kể cả đơn người khác tạo),
     * theo hình thức: method => array(count, total).
     */
    public function shift_payments($user_id, $from, $to)
    {
        $rows = $this->db->select('payment_method, COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total', FALSE)
            ->from('payments')
            ->where('paid_by', (int) $user_id)
            ->where('paid_at >=', $from)
            ->where('paid_at <=', $to)
            ->group_by('payment_method')
            ->get()->result_array();
        $out = array();
        foreach ($rows as $r)
        {
            $out[$r['payment_method']] = array('count' => (int) $r['cnt'], 'total' => (float) $r['total']);
        }
        return $out;
    }

    /** Nhân viên đã từng tạo đơn — cho bộ lọc "Người tạo" ở danh sách Đơn hàng. */
    public function get_creators()
    {
        return $this->db->select('users.id, users.fullname')
            ->distinct()
            ->from($this->table)
            ->join('users', 'users.id = order_sessions.created_by')
            ->order_by('users.fullname', 'ASC')
            ->get()->result_array();
    }

    public function daily_revenue($date)
    {
        return $this->db->select_sum('total_amount')
            ->where('status', 'PAID')
            ->where('DATE(paid_at)', $date)
            ->get($this->table)->row('total_amount');
    }

    public function revenue_by_day($from, $to)
    {
        return $this->db->select('DATE(paid_at) as day, SUM(total_amount) as revenue, COUNT(*) as orders_count')
            ->where('status', 'PAID')
            ->where('paid_at >=', $from.' 00:00:00')
            ->where('paid_at <=', $to.' 23:59:59')
            ->group_by('DATE(paid_at)')
            ->order_by('day', 'ASC')
            ->get($this->table)->result_array();
    }
}
