<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Banner trang chủ (website public): nhiều ảnh, tự chạy dạng băng chuyền (carousel) ở đầu trang chủ.
 * Quản lý ở Quản trị → Website → Banner trang chủ (controllers/Banners.php). Ảnh nằm ở assets/uploads/banners/.
 * Không có banner nào đang hiện thì trang chủ dùng ảnh mặc định (views/public/partials/hero.php).
 */
class Migration_Site_banners extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS site_banners (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            image VARCHAR(255) NOT NULL COMMENT 'Đường dẫn trong assets/ (uploads/banners/...)',
            title VARCHAR(150) NULL COMMENT 'Mô tả ảnh (đọc cho người khiếm thị, hiện khi rê chuột)',
            link_url VARCHAR(300) NULL COMMENT 'Bấm vào banner mở link này (không bắt buộc)',
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_site_banners_show (status, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS site_banners');
    }
}
