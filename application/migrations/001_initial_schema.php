<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Schema gốc của Leno (POS quán cà phê) — gộp toàn bộ 79 migration cũ (kế thừa từ Pick Angel Park,
 * gồm cả các bước thêm rồi gỡ sân pickleball, Trung Thu, gọi món QR, KDS, thu ngân...) thành 1 bước
 * tạo thẳng cấu trúc hiện tại, trước lần deploy production đầu tiên (2026-10-06).
 *
 * Ngoài bảng, chỉ seed dữ liệu tham chiếu để một cài đặt MỚI chạy được: danh mục menu + quyền theo
 * vai trò, cài đặt mặc định và bàn "Mang đi". Không tạo tài khoản đăng nhập — production được nạp
 * từ bản dump dữ liệu (đã có tài khoản); cài mới hoàn toàn thì tạo user ADMIN thủ công.
 * Sinh tự động từ schema đang chạy — sửa schema sau này bằng migration mới (002_...), đừng sửa file này.
 */
class Migration_Initial_schema extends CI_Migration
{
    /** Thứ tự xoá ngược khi down(). */
    private $tables = array (
  0 => 'audit_logs',
  1 => 'cafe_tables',
  2 => 'categories',
  3 => 'dispense_points',
  4 => 'gallery',
  5 => 'inventory_categories',
  6 => 'inventory_items',
  7 => 'inventory_units',
  8 => 'kitchen_tickets',
  9 => 'kitchen_ticket_items',
  10 => 'menu_items',
  11 => 'order_items',
  12 => 'order_sessions',
  13 => 'payments',
  14 => 'payroll_absences',
  15 => 'payroll_bonuses',
  16 => 'payroll_hours',
  17 => 'payroll_rate_history',
  18 => 'payroll_records',
  19 => 'payroll_settings',
  20 => 'products',
  21 => 'promotions',
  22 => 'recipes',
  23 => 'recipe_ingredients',
  24 => 'role_menu_permissions',
  25 => 'settings',
  26 => 'stock_transactions',
  27 => 'table_sessions',
  28 => 'users',
  29 => 'user_menu_permissions',
  30 => 'user_remember_tokens',
);

    public function up()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        $this->db->query('CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_al_module` (`module`),
  KEY `idx_al_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `cafe_tables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `table_code` varchar(20) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `is_takeaway` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum(\'AVAILABLE\',\'OPEN\',\'WAIT_PAYMENT\',\'PAID\') NOT NULL DEFAULT \'AVAILABLE\',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tables_code` (`table_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `dispense_points` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `gallery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `category` enum(\'kids\',\'cafe\',\'photobooth\') NOT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `inventory_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `inventory_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `base_unit` varchar(10) DEFAULT NULL,
  `base_unit_cost` decimal(12,4) DEFAULT NULL,
  `sku` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `storage_type` enum(\'COLD\',\'DRY\') NOT NULL DEFAULT \'DRY\',
  `low_stock_threshold` decimal(12,2) NOT NULL DEFAULT 0.00,
  `qty_on_hand` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventory_items_sku` (`sku`),
  KEY `idx_inventory_items_category` (`category_id`),
  KEY `idx_inventory_items_unit` (`unit_id`),
  CONSTRAINT `fk_inventory_items_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`),
  CONSTRAINT `fk_inventory_items_unit` FOREIGN KEY (`unit_id`) REFERENCES `inventory_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `inventory_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventory_units_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `kitchen_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_session_id` int(10) unsigned NOT NULL,
  `table_id` int(10) unsigned DEFAULT NULL,
  `status` enum(\'NEW\',\'PREPARING\',\'COMPLETED\') NOT NULL DEFAULT \'NEW\',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kt_order` (`order_session_id`),
  KEY `idx_kt_table` (`table_id`),
  KEY `idx_kt_status` (`status`),
  CONSTRAINT `fk_kt_order` FOREIGN KEY (`order_session_id`) REFERENCES `order_sessions` (`id`),
  CONSTRAINT `fk_kt_table` FOREIGN KEY (`table_id`) REFERENCES `cafe_tables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `kitchen_ticket_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL,
  `status` enum(\'NEW\',\'PREPARING\',\'COMPLETED\') NOT NULL DEFAULT \'NEW\',
  PRIMARY KEY (`id`),
  KEY `idx_kti_ticket` (`ticket_id`),
  KEY `idx_kti_product` (`product_id`),
  CONSTRAINT `fk_kti_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_kti_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `kitchen_tickets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_label` varchar(100) DEFAULT NULL,
  `menu_key` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `controller` varchar(100) NOT NULL,
  `methods` varchar(255) DEFAULT NULL,
  `route` varchar(150) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mi_key` (`menu_key`),
  KEY `idx_mi_controller` (`controller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_session_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `notified_qty` int(11) NOT NULL DEFAULT 0,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `notified_note` varchar(255) DEFAULT NULL,
  `status` enum(\'ACTIVE\',\'CANCELLED\') NOT NULL DEFAULT \'ACTIVE\',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_session_id`),
  KEY `idx_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_session_id`) REFERENCES `order_sessions` (`id`),
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `order_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL,
  `order_type` enum(\'DINE_IN\',\'TAKEAWAY\') NOT NULL DEFAULT \'DINE_IN\',
  `table_session_id` int(10) unsigned DEFAULT NULL,
  `status` enum(\'OPEN\',\'WAIT_PAYMENT\',\'PAID\',\'CANCELLED\') NOT NULL DEFAULT \'OPEN\',
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_os_table_session` (`table_session_id`),
  KEY `idx_order_sessions_created_by` (`created_by`),
  CONSTRAINT `fk_os_table_session` FOREIGN KEY (`table_session_id`) REFERENCES `table_sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_session_id` int(10) unsigned NOT NULL,
  `payment_method` enum(\'CASH\',\'CARD\',\'TRANSFER\',\'QR\') NOT NULL DEFAULT \'CASH\',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `received_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_by` int(10) unsigned NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_order` (`order_session_id`),
  KEY `fk_pay_user` (`paid_by`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_session_id`) REFERENCES `order_sessions` (`id`),
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payroll_absences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `absence_date` date NOT NULL,
  `fraction` decimal(3,2) DEFAULT 1.00,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pa_user_date` (`user_id`,`absence_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payroll_bonuses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `period` char(7) NOT NULL,
  `bonus_type` enum(\'AMOUNT\',\'HOURLY\') NOT NULL DEFAULT \'AMOUNT\',
  `hours` decimal(5,2) DEFAULT NULL,
  `rate` decimal(12,2) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pb_user_period` (`user_id`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payroll_hours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ph_user_date` (`user_id`,`work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payroll_rate_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `salary_type` enum(\'FIXED\',\'HOURLY\') NOT NULL,
  `fixed_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `effective_from` char(7) NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prh_user_period` (`user_id`,`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payroll_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `period` char(7) NOT NULL,
  `advance_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_status` enum(\'UNPAID\',\'PAID\') NOT NULL DEFAULT \'UNPAID\',
  `paid_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_user_period` (`user_id`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `payroll_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `salary_type` enum(\'FIXED\',\'HOURLY\') NOT NULL DEFAULT \'FIXED\',
  `fixed_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ps_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `inventory_category_id` int(10) unsigned DEFAULT NULL,
  `track_inventory` tinyint(1) NOT NULL DEFAULT 0,
  `sku` varchar(30) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_inventory_category` (`inventory_category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_products_inventory_category` FOREIGN KEY (`inventory_category_id`) REFERENCES `inventory_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `promotions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `recipes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `yield_quantity` decimal(12,3) DEFAULT NULL,
  `yield_unit` varchar(10) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `recipe_ingredients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` int(10) unsigned NOT NULL,
  `inventory_item_id` int(10) unsigned DEFAULT NULL,
  `source_recipe_id` int(10) unsigned DEFAULT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ri_recipe` (`recipe_id`),
  KEY `fk_ri_item` (`inventory_item_id`),
  KEY `idx_ri_source_recipe` (`source_recipe_id`),
  CONSTRAINT `fk_ri_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `fk_ri_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_source_recipe` FOREIGN KEY (`source_recipe_id`) REFERENCES `recipes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `role_menu_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(20) NOT NULL,
  `menu_item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rmp` (`role`,`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `stock_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(32) DEFAULT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `type` enum(\'IN\',\'OUT\',\'ADJUST\') NOT NULL,
  `qty` decimal(12,2) NOT NULL,
  `dispense_point_id` int(10) unsigned DEFAULT NULL,
  `source` enum(\'MANUAL\',\'EXCEL\') NOT NULL DEFAULT \'MANUAL\',
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_st_item` (`item_id`),
  KEY `idx_st_dispense_point` (`dispense_point_id`),
  KEY `idx_st_created_by` (`created_by`),
  KEY `idx_st_batch` (`batch_id`),
  CONSTRAINT `fk_st_dispense_point` FOREIGN KEY (`dispense_point_id`) REFERENCES `dispense_points` (`id`),
  CONSTRAINT `fk_st_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `table_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int(10) unsigned NOT NULL,
  `session_no` varchar(30) NOT NULL,
  `opened_by` int(10) unsigned DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `status` enum(\'OPEN\',\'CLOSED\') NOT NULL DEFAULT \'OPEN\',
  PRIMARY KEY (`id`),
  KEY `idx_ts_table` (`table_id`),
  KEY `idx_ts_status` (`status`),
  KEY `fk_ts_user` (`opened_by`),
  CONSTRAINT `fk_ts_table` FOREIGN KEY (`table_id`) REFERENCES `cafe_tables` (`id`),
  CONSTRAINT `fk_ts_user` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `role` enum(\'STAFF\',\'BARISTA\',\'CASHIER\',\'ADMIN\',\'STOCKTAKER\') NOT NULL DEFAULT \'STAFF\',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `user_menu_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `menu_item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ump` (`user_id`,`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE `user_remember_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `selector` varchar(32) NOT NULL,
  `validator_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_urt_selector` (`selector`),
  KEY `idx_urt_user` (`user_id`),
  CONSTRAINT `fk_urt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->_seed();
    }

    private function _seed()
    {
        $now = date('Y-m-d H:i:s');

        // Danh mục menu (RBAC động) + quyền mặc định theo vai trò.
        $menu_items = array(
            array('menu_key' => 'dashboard', 'group_label' => NULL, 'label' => 'Tổng quan', 'controller' => 'dashboard', 'methods' => NULL, 'route' => 'dashboard', 'sort_order' => '10'),
            array('menu_key' => 'tables', 'group_label' => NULL, 'label' => 'Bàn', 'controller' => 'tables', 'methods' => NULL, 'route' => 'tables', 'sort_order' => '20'),
            array('menu_key' => 'orders', 'group_label' => NULL, 'label' => 'Đơn hàng', 'controller' => 'orders', 'methods' => NULL, 'route' => 'orders', 'sort_order' => '30'),
            array('menu_key' => 'pha_che', 'group_label' => NULL, 'label' => 'Pha chế', 'controller' => 'pha_che', 'methods' => NULL, 'route' => 'pha-che', 'sort_order' => '55'),
            array('menu_key' => 'inventory.stock_in', 'group_label' => 'Kho hàng', 'label' => 'Nhập kho', 'controller' => 'stock', 'methods' => 'in,in_import,in_import_template', 'route' => 'stock/in', 'sort_order' => '100'),
            array('menu_key' => 'inventory.stock_out', 'group_label' => 'Kho hàng', 'label' => 'Xuất kho', 'controller' => 'stock', 'methods' => 'out', 'route' => 'stock/out', 'sort_order' => '110'),
            array('menu_key' => 'inventory.stock_adjust', 'group_label' => 'Kho hàng', 'label' => 'Kiểm kho', 'controller' => 'stock', 'methods' => 'adjust', 'route' => 'stock/adjust', 'sort_order' => '120'),
            array('menu_key' => 'inventory.items', 'group_label' => 'Kho hàng', 'label' => 'Hàng trong kho', 'controller' => 'inventory_items', 'methods' => 'index,create,edit,delete,import,import_template,export,print_list', 'route' => 'inventory/items', 'sort_order' => '130'),
            array('menu_key' => 'inventory.history', 'group_label' => 'Kho hàng', 'label' => 'Lịch sử nhập/xuất', 'controller' => 'stock', 'methods' => 'history', 'route' => 'stock/history', 'sort_order' => '140'),
            array('menu_key' => 'admin.categories', 'group_label' => 'Quản trị', 'label' => 'Danh mục', 'controller' => 'categories', 'methods' => NULL, 'route' => 'categories', 'sort_order' => '200'),
            array('menu_key' => 'admin.products', 'group_label' => 'Quản trị', 'label' => 'Sản phẩm', 'controller' => 'products', 'methods' => NULL, 'route' => 'products', 'sort_order' => '210'),
            array('menu_key' => 'admin.inventory_categories', 'group_label' => 'Quản trị', 'label' => 'Danh mục kho', 'controller' => 'inventory_categories', 'methods' => NULL, 'route' => 'inventory/categories', 'sort_order' => '220'),
            array('menu_key' => 'admin.inventory_units', 'group_label' => 'Quản trị', 'label' => 'Đơn vị tính', 'controller' => 'inventory_units', 'methods' => NULL, 'route' => 'inventory/units', 'sort_order' => '230'),
            array('menu_key' => 'admin.dispense_points', 'group_label' => 'Quản trị', 'label' => 'Điểm xuất kho', 'controller' => 'dispense_points', 'methods' => NULL, 'route' => 'inventory/dispense-points', 'sort_order' => '240'),
            array('menu_key' => 'admin.users', 'group_label' => 'Quản trị', 'label' => 'Người dùng', 'controller' => 'users', 'methods' => NULL, 'route' => 'users', 'sort_order' => '250'),
            array('menu_key' => 'admin.payroll', 'group_label' => 'Quản trị', 'label' => 'Quản lý lương', 'controller' => 'payroll', 'methods' => 'admin,settings,record,hours', 'route' => 'payroll/admin', 'sort_order' => '260'),
            array('menu_key' => 'admin.audit_logs', 'group_label' => 'Quản trị', 'label' => 'Nhật ký hệ thống', 'controller' => 'audit_logs', 'methods' => NULL, 'route' => 'audit-logs', 'sort_order' => '275'),
            array('menu_key' => 'admin.settings', 'group_label' => 'Quản trị', 'label' => 'Cài đặt', 'controller' => 'settings', 'methods' => NULL, 'route' => 'settings', 'sort_order' => '280'),
        );
        $ids = array();
        foreach ($menu_items as $mi)
        {
            $this->db->insert('menu_items', $mi);
            $ids[$mi['menu_key']] = $this->db->insert_id();
        }

        $role_permissions = array(
            array('role' => 'ADMIN', 'menu_key' => 'dashboard'),
            array('role' => 'BARISTA', 'menu_key' => 'dashboard'),
            array('role' => 'CASHIER', 'menu_key' => 'dashboard'),
            array('role' => 'STAFF', 'menu_key' => 'dashboard'),
            array('role' => 'ADMIN', 'menu_key' => 'tables'),
            array('role' => 'ADMIN', 'menu_key' => 'orders'),
            array('role' => 'ADMIN', 'menu_key' => 'pha_che'),
            array('role' => 'ADMIN', 'menu_key' => 'inventory.stock_in'),
            array('role' => 'BARISTA', 'menu_key' => 'inventory.stock_in'),
            array('role' => 'CASHIER', 'menu_key' => 'inventory.stock_in'),
            array('role' => 'STAFF', 'menu_key' => 'inventory.stock_in'),
            array('role' => 'STOCKTAKER', 'menu_key' => 'inventory.stock_in'),
            array('role' => 'ADMIN', 'menu_key' => 'inventory.stock_out'),
            array('role' => 'BARISTA', 'menu_key' => 'inventory.stock_out'),
            array('role' => 'CASHIER', 'menu_key' => 'inventory.stock_out'),
            array('role' => 'STAFF', 'menu_key' => 'inventory.stock_out'),
            array('role' => 'STOCKTAKER', 'menu_key' => 'inventory.stock_out'),
            array('role' => 'ADMIN', 'menu_key' => 'inventory.stock_adjust'),
            array('role' => 'BARISTA', 'menu_key' => 'inventory.stock_adjust'),
            array('role' => 'CASHIER', 'menu_key' => 'inventory.stock_adjust'),
            array('role' => 'STAFF', 'menu_key' => 'inventory.stock_adjust'),
            array('role' => 'STOCKTAKER', 'menu_key' => 'inventory.stock_adjust'),
            array('role' => 'ADMIN', 'menu_key' => 'inventory.items'),
            array('role' => 'BARISTA', 'menu_key' => 'inventory.items'),
            array('role' => 'CASHIER', 'menu_key' => 'inventory.items'),
            array('role' => 'STAFF', 'menu_key' => 'inventory.items'),
            array('role' => 'STOCKTAKER', 'menu_key' => 'inventory.items'),
            array('role' => 'ADMIN', 'menu_key' => 'inventory.history'),
            array('role' => 'BARISTA', 'menu_key' => 'inventory.history'),
            array('role' => 'CASHIER', 'menu_key' => 'inventory.history'),
            array('role' => 'STAFF', 'menu_key' => 'inventory.history'),
            array('role' => 'STOCKTAKER', 'menu_key' => 'inventory.history'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.categories'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.products'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.inventory_categories'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.inventory_units'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.dispense_points'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.users'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.payroll'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.audit_logs'),
            array('role' => 'ADMIN', 'menu_key' => 'admin.settings'),
        );
        foreach ($role_permissions as $rp)
        {
            $this->db->insert('role_menu_permissions', array('role' => $rp['role'], 'menu_item_id' => $ids[$rp['menu_key']]));
        }

        // Cài đặt mặc định.
        $settings = array(
            array('setting_key' => 'vat_percent', 'setting_value' => '0'),
            array('setting_key' => 'site_name', 'setting_value' => 'Pick Angel Park'),
            array('setting_key' => 'site_phone', 'setting_value' => '0974749277'),
            array('setting_key' => 'site_address', 'setting_value' => '82 Võ Văn Kiệt, Buôn Ma Thuột, Đắk Lắk'),
            array('setting_key' => 'site_zalo', 'setting_value' => ''),
            array('setting_key' => 'site_facebook', 'setting_value' => ''),
            array('setting_key' => 'site_tiktok', 'setting_value' => ''),
            array('setting_key' => 'site_google_maps', 'setting_value' => ''),
            array('setting_key' => 'takeaway_enabled', 'setting_value' => '1'),
        );
        foreach ($settings as $s)
        {
            $this->db->insert('settings', $s + array('updated_at' => $now));
        }

        // Bàn đặc biệt "Mang đi" (bán mang đi dùng chung luồng bàn).
        $this->db->insert('cafe_tables', array(
            'table_code' => 'MANG-DI', 'table_name' => 'Mang đi', 'sort_order' => 0, 'capacity' => 0,
            'is_takeaway' => 1, 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now,
        ));
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tables as $t)
        {
            $this->dbforge->drop_table($t, TRUE);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
