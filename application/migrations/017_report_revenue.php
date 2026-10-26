<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khôi phục "Báo cáo doanh thu" nhập tay theo tháng (đã xoá ở migration 071 cũ, commit f822a82) và đưa vào menu "Báo cáo":
 *  - bảng monthly_revenue (tạo lại trống — dữ liệu cũ đã bị xoá cùng bảng; khôi phục từ bản sao lưu nếu cần);
 *    category lưu chữ (không ENUM) — danh sách mảng nằm ở Monthly_revenue_model::CATEGORIES, thêm mảng không cần sửa bảng;
 *  - mục menu "reports.revenue" (nhóm "Báo cáo") để gán quyền xem + nhập ở Quản trị → Gán quyền menu.
 */
class Migration_Report_revenue extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS monthly_revenue (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            period CHAR(7) NOT NULL COMMENT 'YYYY-MM',
            category VARCHAR(30) NOT NULL COMMENT 'Mã mảng — Monthly_revenue_model::CATEGORIES',
            revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
            note VARCHAR(255) NULL,
            updated_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_mr_period_category (period, category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ( ! $this->db->where('menu_key', 'reports.revenue')->count_all_results('menu_items'))
        {
            // methods NULL = mọi trang của controller revenue_report (xem + nhập doanh thu).
            $this->db->insert('menu_items', array(
                'group_label' => 'Báo cáo', 'menu_key' => 'reports.revenue', 'label' => 'Báo cáo doanh thu', 'icon' => NULL,
                'controller' => 'revenue_report', 'methods' => NULL, 'route' => 'reports/revenue', 'sort_order' => 290,
            ));
        }
    }

    public function down()
    {
        $item = $this->db->where('menu_key', 'reports.revenue')->get('menu_items')->row_array();
        if ($item)
        {
            $this->db->where('menu_item_id', $item['id'])->delete('role_menu_permissions');
            if ($this->db->table_exists('user_menu_permissions')) $this->db->where('menu_item_id', $item['id'])->delete('user_menu_permissions');
            $this->db->where('id', $item['id'])->delete('menu_items');
        }
        $this->db->query('DROP TABLE IF EXISTS monthly_revenue');
    }
}
