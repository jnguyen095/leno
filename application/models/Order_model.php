<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_model extends CI_Model
{
    protected $table = 'order_sessions';

    public function create_for_table_session($table_session_id, $order_type = 'DINE_IN')
    {
        $data = array(
            'order_no'          => gen_no('ORD'),
            'order_type'        => $order_type,
            'table_session_id'  => $table_session_id,
            'status'            => 'OPEN',
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
     * Closes any stray OPEN sessions first, opens a fresh one, creates its
     * order, and flips the table to OPEN. Returns ['session_id'=>, 'order_id'=>].
     */
    public function open_table_with_order($table_id, $opened_by = NULL)
    {
        $this->load->model(array('Table_model', 'Table_session_model'));

        $this->Table_session_model->close_stray_open_sessions($table_id);
        $session_id = $this->Table_session_model->open($table_id, $opened_by);
        $table = $this->Table_model->get_by_id($table_id);
        $order_id = $this->create_for_table_session($session_id, ( ! empty($table['is_takeaway'])) ? 'TAKEAWAY' : 'DINE_IN');
        $this->Table_model->set_status($table_id, 'OPEN');

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
    public function get_active_orders()
    {
        return $this->db->select('order_sessions.id, order_sessions.order_no, order_sessions.total_amount, order_sessions.status, table_sessions.table_id, cafe_tables.table_name, cafe_tables.is_takeaway')
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left')
            ->where_in('order_sessions.status', array('OPEN', 'WAIT_PAYMENT'))
            ->order_by('cafe_tables.is_takeaway', 'DESC')
            ->order_by('cafe_tables.sort_order', 'ASC')
            ->order_by('order_sessions.id', 'ASC')
            ->get()->result_array();
    }

    public function get_detail($id)
    {
        return $this->db->select('order_sessions.*, table_sessions.table_id, cafe_tables.table_name, cafe_tables.table_code')
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left')
            ->where('order_sessions.id', $id)
            ->get()->row_array();
    }

    public function get_list($filters = array(), $limit = NULL, $offset = 0)
    {
        $this->db->select('order_sessions.*, cafe_tables.table_name, cafe_tables.table_code')
            ->from($this->table)
            ->join('table_sessions', 'table_sessions.id = order_sessions.table_session_id', 'left')
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id', 'left');

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
