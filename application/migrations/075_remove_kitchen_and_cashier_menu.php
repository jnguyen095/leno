<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bỏ 2 màn hình không còn dùng trong luồng POS mới:
 *  - "Bếp (KDS)" (/me/kitchen): bếp nhận món qua phiếu in khi bấm "Thông báo" ở trang đơn.
 *    Bảng kitchen_tickets/kitchen_ticket_items vẫn giữ làm lịch sử các lần báo bếp.
 *  - "Thu ngân" (/me/cashier): thanh toán + in hóa đơn làm ngay trên trang đơn.
 * Chỉ xoá mục menu + quyền gán theo vai trò/nhân viên; dữ liệu đơn/thanh toán giữ nguyên.
 */
class Migration_Remove_kitchen_and_cashier_menu extends CI_Migration
{
    public function up()
    {
        $menu_ids = array_column($this->db->select('id')->where_in('menu_key', array('kitchen', 'cashier'))->get('menu_items')->result_array(), 'id');
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
