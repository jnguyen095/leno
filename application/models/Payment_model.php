<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_model extends CI_Model
{
    protected $table = 'payments';

    public function create($order_session_id, $method, $amount, $received, $paid_by)
    {
        $change = max(0, $received - $amount);
        $data = array(
            'order_session_id' => $order_session_id,
            'payment_method'   => $method,
            'amount'           => $amount,
            'received_amount'  => $received,
            'change_amount'    => $change,
            'paid_by'          => $paid_by,
            'paid_at'          => date('Y-m-d H:i:s'),
        );
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function get_by_order($order_session_id)
    {
        return $this->db->where('order_session_id', $order_session_id)->order_by('id', 'DESC')->get($this->table)->row_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }
}
