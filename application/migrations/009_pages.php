<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang nội dung cho website (Giới thiệu, Khuyến mãi...): quản trị soạn ở /me/pages (CKEditor),
 * khách xem ở /page/<code>.html (controllers/public/Site_page.php).
 * Ảnh chèn vào nội dung nằm ở assets/uploads/pages/ (quản lý ở /me/pages/images), không lưu bảng riêng.
 */
class Migration_Pages extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS pages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(100) NOT NULL COMMENT 'Đường dẫn: /page/<code>.html (a-z, 0-9, -)',
            title VARCHAR(200) NOT NULL,
            description MEDIUMTEXT NULL COMMENT 'Nội dung HTML (CKEditor); link nội bộ lưu dạng {{base_url}}',
            status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = bật (hiện trên website), 0 = tắt',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_pages_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS pages');
    }
}
