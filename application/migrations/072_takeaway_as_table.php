<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bán mang đi chuyển vào Sơ đồ bàn: thay trang /me/takeaway/create (đơn không gắn bàn)
 * bằng 1 "bàn" đặc biệt tên "Mang đi" (cafe_tables.is_takeaway = 1) — mở/gọi món/bếp/
 * thanh toán y hệt bàn thường. Chỉ hiện trên sơ đồ khi bật cài đặt takeaway_enabled.
 * Bàn này không có QR gọi món, không sửa/xoá được ở Quản lý bàn.
 */
class Migration_Takeaway_as_table extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN is_takeaway TINYINT(1) NOT NULL DEFAULT 0 AFTER capacity');

        $now = date('Y-m-d H:i:s');
        $this->db->insert('cafe_tables', array(
            'table_code'  => 'MANG-DI',
            'table_name'  => 'Mang đi',
            'sort_order'  => 0,
            'capacity'    => 0,
            'is_takeaway' => 1,
            'qr_token'    => bin2hex(random_bytes(16)),
            'status'      => 'AVAILABLE',
            'created_at'  => $now,
            'updated_at'  => $now,
        ));

        // Mặc định bật để giữ nguyên khả năng bán mang đi như trước.
        $this->db->insert('settings', array('setting_key' => 'takeaway_enabled', 'setting_value' => '1', 'updated_at' => $now));

        // Menu "Bán mang đi" cũ không còn trang riêng.
        $menu_ids = array_column($this->db->select('id')->where('menu_key', 'takeaway')->get('menu_items')->result_array(), 'id');
        if ($menu_ids)
        {
            $this->db->where_in('menu_item_id', $menu_ids)->delete('role_menu_permissions');
            $this->db->where_in('menu_item_id', $menu_ids)->delete('user_menu_permissions');
            $this->db->where_in('id', $menu_ids)->delete('menu_items');
        }
    }

    public function down()
    {
        $this->db->where('setting_key', 'takeaway_enabled')->delete('settings');
        $this->db->where('is_takeaway', 1)->delete('cafe_tables');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN is_takeaway');
    }
}
