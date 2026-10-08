<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Màn hình khách (màn hình phụ của máy POS): ảnh trình chiếu lúc rảnh + mục menu quản trị.
 * Các tuỳ chọn hiển thị lưu trong bảng settings (khóa display_*), xem Setting_model::get_display_config().
 */
class Migration_Customer_display extends CI_Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS `display_slides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `duration_seconds` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ds_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Mục "Màn hình khách" trong menu Quản trị (chỉ ADMIN; gán thêm qua "Gán quyền menu").
        $exists = $this->db->where('menu_key', 'admin.customer_display')->get('menu_items')->row_array();
        if ( ! $exists)
        {
            $this->db->insert('menu_items', array(
                'menu_key'    => 'admin.customer_display',
                'group_label' => 'Quản trị',
                'label'       => 'Màn hình khách',
                'controller'  => 'customer_display',
                'methods'     => NULL,
                'route'       => 'customer-display',
                'sort_order'  => 278,
            ));
            $this->db->insert('role_menu_permissions', array('role' => 'ADMIN', 'menu_item_id' => $this->db->insert_id()));
        }
    }

    public function down()
    {
        $item = $this->db->where('menu_key', 'admin.customer_display')->get('menu_items')->row_array();
        if ($item)
        {
            $this->db->where('menu_item_id', $item['id'])->delete('role_menu_permissions');
            $this->db->where('menu_item_id', $item['id'])->delete('user_menu_permissions');
            $this->db->where('id', $item['id'])->delete('menu_items');
        }
        $this->db->query('DROP TABLE IF EXISTS `display_slides`');
    }
}
