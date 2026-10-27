<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category_model extends CI_Model
{
    protected $table = 'categories';

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get($this->table)->result_array();
    }

    public function get_active()
    {
        return $this->db->where('status', 'ACTIVE')->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'INACTIVE'));
    }

    /** Thứ tự cho danh mục mới: xếp cuối danh sách. */
    public function next_sort_order()
    {
        return (int) $this->db->select_max('sort_order')->get($this->table)->row('sort_order') + 1;
    }

    /**
     * Sắp xếp lại theo danh sách id (kéo thả / nhập số ở trang Danh mục): sort_order = 1, 2, 3... theo đúng thứ tự gửi lên.
     * $ids phải gồm đủ mọi danh mục (không thiếu, không thừa, không trùng). Trả về FALSE nếu không hợp lệ.
     */
    public function reorder(array $ids)
    {
        $ids = array_map('intval', $ids);
        $all = array_map('intval', array_column($this->db->select('id')->get($this->table)->result_array(), 'id'));
        if (count($ids) !== count($all) || count(array_unique($ids)) !== count($ids) || array_diff($all, $ids))
        {
            return FALSE;
        }
        $this->db->trans_start();
        foreach ($ids as $i => $id)
        {
            $this->db->where('id', $id)->update($this->table, array('sort_order' => $i + 1));
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
