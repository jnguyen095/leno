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

        $this->load->model('Setting_model');
        $vat = round($subtotal * $this->Setting_model->get_vat_rate());
        $total = $subtotal - (float) $order['discount_amount'] + $vat;

        $this->db->where('id', $order_id)->update($this->table, array(
            'subtotal'     => $subtotal,
            'vat_amount'   => $vat,
            'total_amount' => max(0, $total),
        ));
    }

    public function set_discount($order_id, $discount_amount)
    {
        $this->db->where('id', $order_id)->update($this->table, array('discount_amount' => $discount_amount));
        $this->recalc_totals($order_id);
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
        $this->db->order_by('order_sessions.id', 'DESC');

        if ($limit !== NULL)
        {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result_array();
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
        if ( ! empty($filters['date']))
        {
            $this->db->where('DATE(order_sessions.created_at)', $filters['date']);
        }
        if ( ! empty($filters['date_from']))
        {
            $this->db->where('order_sessions.created_at >=', $filters['date_from'].' 00:00:00');
        }
        if ( ! empty($filters['date_to']))
        {
            $this->db->where('order_sessions.created_at <=', $filters['date_to'].' 23:59:59');
        }
        if ( ! empty($filters['table_id']))
        {
            $this->db->where('cafe_tables.id', $filters['table_id']);
        }
        if ( ! empty($filters['created_by']))
        {
            $this->db->where('order_sessions.created_by', (int) $filters['created_by']);
        }
        // Phương thức thanh toán (CASH/TRANSFER/CARD/QR) hoặc NONE = đơn chưa có thanh toán.
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
            ->where('order_sessions.created_at >=', $date.' 00:00:00')
            ->where('order_sessions.created_at <=', $date.' 23:59:59')
            ->having('item_count >', 0)
            ->order_by('order_sessions.created_at', 'DESC')
            ->order_by('order_sessions.id', 'DESC')
            ->get()->result_array();
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
