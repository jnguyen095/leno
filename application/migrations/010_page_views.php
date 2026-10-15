<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lượt xem trang nội dung (/page/<code>.html): tăng khi khách mở trang — không tính nhân viên đã đăng nhập,
 * bot tìm kiếm và lần tải lại của cùng trình duyệt trong 30 phút (xem Site_page::_count_view).
 */
class Migration_Page_views extends CI_Migration
{
    public function up()
    {
        if ( ! $this->db->field_exists('view_count', 'pages'))
        {
            $this->db->query("ALTER TABLE pages
                ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lượt khách xem' AFTER status,
                ADD COLUMN last_viewed_at DATETIME NULL COMMENT 'Lần xem gần nhất' AFTER view_count");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('view_count', 'pages'))
        {
            $this->db->query('ALTER TABLE pages DROP COLUMN view_count, DROP COLUMN last_viewed_at');
        }
    }
}
