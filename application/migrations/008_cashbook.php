<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sổ quỹ: phiếu Thu / Chi nhập tay (cashbook_entries) + danh mục thu / chi do quản trị sửa được (cashbook_categories).
 * Tiền bán hàng (bảng payments của đơn đã thanh toán) KHÔNG chép vào đây — Sổ quỹ đọc thẳng từ payments và hiện thành
 * phiếu thu "Bán hàng" chỉ xem, nên luôn khớp với đơn hàng.
 * Thêm menu "Sổ quỹ" (menu_items) để gán quyền cho vai trò khác ở Gán quyền menu.
 */
class Migration_Cashbook extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS cashbook_categories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            type ENUM('IN','OUT') NOT NULL COMMENT 'IN = loại thu, OUT = loại chi',
            name VARCHAR(100) NOT NULL,
            status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_cashbook_categories_type (type, status, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS cashbook_entries (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            entry_no VARCHAR(20) NOT NULL COMMENT 'PT000001 (thu) / PC000001 (chi)',
            type ENUM('IN','OUT') NOT NULL,
            entry_date DATETIME NOT NULL,
            payment_method ENUM('CASH','TRANSFER') NOT NULL DEFAULT 'CASH',
            category_id INT UNSIGNED NULL,
            counterparty VARCHAR(150) NULL COMMENT 'Thu: nguồn / người nộp; Chi: người nhận',
            handled_by INT UNSIGNED NULL COMMENT 'Nhân viên thu / chi',
            amount DECIMAL(14,2) NOT NULL,
            note VARCHAR(255) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_cashbook_entry_no (entry_no),
            KEY idx_cashbook_entries_date (entry_date),
            KEY idx_cashbook_entries_type (type, entry_date),
            KEY idx_cashbook_entries_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $now = date('Y-m-d H:i:s');
        $seed = array(
            'IN'  => array('Thu khác', 'Góp vốn', 'Thu nợ', 'Hoàn tiền nhà cung cấp'),
            'OUT' => array('Nhập hàng', 'Lương nhân viên', 'Điện, nước, internet', 'Thuê mặt bằng', 'Sửa chữa, bảo trì', 'Chi khác'),
        );
        foreach ($seed as $type => $names)
        {
            foreach ($names as $i => $name)
            {
                $this->db->insert('cashbook_categories', array('type' => $type, 'name' => $name, 'sort_order' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now));
            }
        }

        if ( ! $this->db->where('menu_key', 'cashbook')->count_all_results('menu_items'))
        {
            $this->db->insert('menu_items', array(
                'group_label' => NULL, 'menu_key' => 'cashbook', 'label' => 'Sổ quỹ', 'icon' => NULL,
                'controller' => 'cashbook', 'methods' => NULL, 'route' => 'cashbook', 'sort_order' => 35,
            ));
        }
    }

    public function down()
    {
        $item = $this->db->where('menu_key', 'cashbook')->get('menu_items')->row_array();
        if ($item)
        {
            $this->db->where('menu_item_id', $item['id'])->delete('role_menu_permissions');
            if ($this->db->table_exists('user_menu_permissions')) $this->db->where('menu_item_id', $item['id'])->delete('user_menu_permissions');
            $this->db->where('id', $item['id'])->delete('menu_items');
        }
        $this->db->query('DROP TABLE IF EXISTS cashbook_entries');
        $this->db->query('DROP TABLE IF EXISTS cashbook_categories');
    }
}
