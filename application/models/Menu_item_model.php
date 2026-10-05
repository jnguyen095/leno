<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Danh mục menu có thể gán quyền — mỗi dòng khớp 1 (controller, danh sách method). */
class Menu_item_model extends CI_Model
{
    protected $table = 'menu_items';

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_key($menu_key)
    {
        return $this->db->where('menu_key', $menu_key)->get($this->table)->row_array();
    }

    /** Tất cả menu_items khớp 1 controller (không lọc theo method — lọc ở PHP để so khớp danh sách methods). */
    public function get_by_controller($controller)
    {
        return $this->db->where('controller', strtolower($controller))->get($this->table)->result_array();
    }
}
