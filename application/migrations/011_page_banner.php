<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ảnh banner (không bắt buộc) của trang nội dung: hiện tràn ngang ở đầu trang /page/<code>.html.
 * Lưu đường dẫn tương đối trong assets/ (vd uploads/pages/abc.jpg) — ảnh nằm chung Thư viện ảnh.
 */
class Migration_Page_banner extends CI_Migration
{
    public function up()
    {
        if ( ! $this->db->field_exists('banner', 'pages'))
        {
            $this->db->query("ALTER TABLE pages ADD COLUMN banner VARCHAR(255) NULL COMMENT 'Ảnh banner đầu trang (đường dẫn trong assets/)' AFTER title");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('banner', 'pages'))
        {
            $this->db->query('ALTER TABLE pages DROP COLUMN banner');
        }
    }
}
