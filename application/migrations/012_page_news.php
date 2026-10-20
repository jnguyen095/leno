<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tin tức / cập nhật: dùng chung bảng pages với Trang nội dung (cùng trình soạn thảo, thư viện ảnh, banner, lượt xem).
 *  - type: PAGE = trang nội dung (/page/<code>.html), NEWS = tin tức (/tin-tuc/<code>.html, hiện ở trang chủ).
 *  - summary: tóm tắt ngắn hiện trên thẻ tin (rỗng thì lấy đoạn đầu nội dung).
 *  - published_at: ngày đăng; ngày trong tương lai = hẹn giờ, chưa hiện cho khách.
 *  - is_pinned: ghim lên đầu danh sách tin.
 */
class Migration_Page_news extends CI_Migration
{
    public function up()
    {
        if ( ! $this->db->field_exists('type', 'pages'))
        {
            $this->db->query("ALTER TABLE pages
                ADD COLUMN type ENUM('PAGE','NEWS') NOT NULL DEFAULT 'PAGE' COMMENT 'PAGE = trang nội dung, NEWS = tin tức' AFTER code,
                ADD COLUMN summary VARCHAR(300) NULL COMMENT 'Tóm tắt trên thẻ tin' AFTER title,
                ADD COLUMN published_at DATETIME NULL COMMENT 'Ngày đăng (tin tức); tương lai = hẹn giờ' AFTER status,
                ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ghim tin lên đầu' AFTER published_at,
                ADD KEY idx_pages_news (type, status, published_at)");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('type', 'pages'))
        {
            $this->db->query('ALTER TABLE pages DROP KEY idx_pages_news, DROP COLUMN type, DROP COLUMN summary, DROP COLUMN published_at, DROP COLUMN is_pinned');
        }
    }
}
