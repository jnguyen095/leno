<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bỏ trang "LS Thanh toán" (/me/payments) — phương thức thanh toán đã hiện ngay ở danh sách
 * Đơn hàng. Chỉ xoá mục menu + quyền; bảng payments vẫn giữ (đơn hàng, hóa đơn dùng tới).
 */
class Migration_Remove_payments_menu extends CI_Migration
{
    public function up()
    {
        $menu_ids = array_column($this->db->select('id')->where('menu_key', 'payments')->get('menu_items')->result_array(), 'id');
        if ($menu_ids)
        {
            $this->db->where_in('menu_item_id', $menu_ids)->delete('role_menu_permissions');
            $this->db->where_in('menu_item_id', $menu_ids)->delete('user_menu_permissions');
            $this->db->where_in('id', $menu_ids)->delete('menu_items');
        }
    }

    public function down()
    {
        // Mục menu đã xoá cùng controller của nó — muốn khôi phục thì lấy lại code + bản sao lưu DB.
    }
}
