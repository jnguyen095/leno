<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_item_model extends CI_Model
{
    protected $table = 'order_items';

    public function add($order_session_id, $product_id, $qty, $price, $note = NULL)
    {
        $data = array(
            'order_session_id' => $order_session_id,
            'product_id'       => $product_id,
            'qty'              => $qty,
            'price'            => $price,
            'amount'           => $price * $qty,
            'note'             => $note,
            'status'           => 'ACTIVE',
            'created_at'       => date('Y-m-d H:i:s'),
        );
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_qty($id, $qty)
    {
        $item = $this->get_by_id($id);
        if ( ! $item) return FALSE;

        return $this->db->where('id', $id)->update($this->table, array(
            'qty'    => $qty,
            'amount' => $item['price'] * $qty,
        ));
    }

    public function cancel($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'CANCELLED'));
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_order($order_session_id)
    {
        return $this->db->select('order_items.*, products.product_name, products.image')
            ->from($this->table)
            ->join('products', 'products.id = order_items.product_id')
            ->where('order_session_id', $order_session_id)
            ->order_by('order_items.id', 'ASC')
            ->get()->result_array();
    }

    public function get_active_by_order($order_session_id)
    {
        return $this->db->select('order_items.*, products.product_name, products.image, products.sku')
            ->from($this->table)
            ->join('products', 'products.id = order_items.product_id')
            ->where('order_session_id', $order_session_id)
            ->where('order_items.status', 'ACTIVE')
            ->order_by('order_items.id', 'ASC')
            ->get()->result_array();
    }
}
