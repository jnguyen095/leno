<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xoá hẳn 2 tính năng kế thừa từ Pick Angel Park:
 *  - Đăng ký quà Trung Thu (trang public /trung-thu + trang quản trị) -> bảng trung_thu_registrations
 *    và 2 cài đặt mở/đóng đăng ký.
 *  - Báo cáo doanh thu nhập tay theo tháng (/me/reports) -> bảng monthly_revenue và menu "admin.reports".
 * Nhật ký hệ thống của 2 module này cũng bị xoá. Không rollback được — khôi phục từ bản sao lưu DB.
 */
class Migration_Remove_trung_thu_and_revenue_report extends CI_Migration
{
    public function up()
    {
        $this->dbforge->drop_table('trung_thu_registrations', TRUE);
        $this->dbforge->drop_table('monthly_revenue', TRUE);

        $this->db->where_in('setting_key', array('trung_thu_open_at', 'trung_thu_close_at'))->delete('settings');

        $menu_ids = array_column($this->db->select('id')->where('menu_key', 'admin.reports')->get('menu_items')->result_array(), 'id');
        if ($menu_ids)
        {
            $this->db->where_in('menu_item_id', $menu_ids)->delete('role_menu_permissions');
            $this->db->where_in('menu_item_id', $menu_ids)->delete('user_menu_permissions');
            $this->db->where_in('id', $menu_ids)->delete('menu_items');
        }

        $this->db->where_in('module', array('trung_thu_registration', 'monthly_revenue'))->delete('audit_logs');
    }

    public function down()
    {
        show_error('Migration 071 (xoá Trung Thu + báo cáo doanh thu) không rollback được — khôi phục từ bản sao lưu DB.');
    }
}
