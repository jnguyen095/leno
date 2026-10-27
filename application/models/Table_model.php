<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Table_model extends CI_Model
{
    protected $table = 'cafe_tables';

    /** Bàn thường. $include_takeaway = TRUE để lấy kèm bàn "Mang đi" (is_takeaway = 1). */
    public function get_all($include_takeaway = FALSE)
    {
        if ( ! $include_takeaway)
        {
            $this->db->where('is_takeaway', 0);
        }
        return $this->db->order_by('is_takeaway', 'DESC')->order_by('sort_order', 'ASC')->order_by('table_name', 'ASC')->get($this->table)->result_array();
    }

    public function get_takeaway()
    {
        return $this->db->where('is_takeaway', 1)->get($this->table)->row_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_code($code)
    {
        return $this->db->where('table_code', $code)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    /** Thứ tự cho bàn mới: xếp cuối danh sách bàn thường. */
    public function next_sort_order()
    {
        return (int) $this->db->select_max('sort_order')->where('is_takeaway', 0)->get($this->table)->row('sort_order') + 1;
    }

    /**
     * Sắp xếp lại bàn thường (trang Quản lý bàn: kéo thả / nhập số): sort_order = 1, 2, 3... theo đúng thứ tự $ids.
     * Bàn "Mang đi" không nằm trong danh sách này (luôn đứng đầu). $ids phải đủ mọi bàn thường, không trùng — sai thì FALSE.
     */
    public function reorder(array $ids)
    {
        $ids = array_map('intval', $ids);
        $all = array_map('intval', array_column($this->db->select('id')->where('is_takeaway', 0)->get($this->table)->result_array(), 'id'));
        if (count($ids) !== count($all) || count(array_unique($ids)) !== count($ids) || array_diff($all, $ids))
        {
            return FALSE;
        }
        $this->db->trans_start();
        foreach ($ids as $i => $id)
        {
            $this->db->where('id', $id)->where('is_takeaway', 0)->update($this->table, array('sort_order' => $i + 1, 'updated_at' => date('Y-m-d H:i:s')));
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function set_note($id, $note)
    {
        return $this->update($id, array('note' => $note));
    }

    public function set_status($id, $status)
    {
        return $this->update($id, array('status' => $status));
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function code_exists($code, $except_id = NULL)
    {
        $this->db->where('table_code', $code);
        if ($except_id)
        {
            $this->db->where('id !=', $except_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }
}
