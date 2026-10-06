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

    /**
     * Thêm món vào đơn — gộp vào dòng ACTIVE cùng sản phẩm (không ghi chú) nếu đã có,
     * để bấm thêm nhiều lần không sinh nhiều dòng; phần tăng thêm sẽ được báo bếp ở
     * lần "Thông báo" kế tiếp (qty > notified_qty).
     */
    public function add_or_merge($order_session_id, $product_id, $qty, $price, $note = NULL)
    {
        if ($note === NULL || $note === '')
        {
            $existing = $this->db->where('order_session_id', $order_session_id)
                ->where('product_id', $product_id)
                ->where('status', 'ACTIVE')
                ->group_start()->where('note IS NULL', NULL, FALSE)->or_where('note', '')->group_end()
                ->order_by('id', 'DESC')->limit(1)
                ->get($this->table)->row_array();
            if ($existing)
            {
                $this->update_qty($existing['id'], $existing['qty'] + $qty);
                return $existing['id'];
            }
        }
        return $this->add($order_session_id, $product_id, $qty, $price, $note ?: NULL);
    }

    /** Ghi lại phần bếp đã nhận ở lần "Thông báo": số lượng + ghi chú tại thời điểm đó. */
    public function set_notified($id, $qty, $note)
    {
        return $this->db->where('id', $id)->update($this->table, array('notified_qty' => $qty, 'notified_note' => $note));
    }

    public function set_note($id, $note)
    {
        return $this->db->where('id', $id)->update($this->table, array('note' => $note));
    }

    /** Món đã báo bếp nhưng ghi chú đã sửa sau đó -> cần báo lại cho bếp. */
    public static function note_changed($it)
    {
        return $it['status'] === 'ACTIVE' && (int) $it['notified_qty'] > 0
            && (string) $it['note'] !== (string) $it['notified_note'];
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

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
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
