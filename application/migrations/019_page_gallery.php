<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bộ sưu tập ảnh cho từng trang nội dung / tin (pages.gallery): hiện cuối trang trên website dạng băng chuyền,
 * bấm ảnh mở xem lớn. Lưu JSON [{"image":"uploads/pages/x.jpg","caption":"..."}] theo đúng thứ tự hiện —
 * ảnh nằm chung thư mục Thư viện ảnh (assets/uploads/pages/). Soạn ở Quản trị → Trang nội dung → Sửa trang.
 */
class Migration_Page_gallery extends CI_Migration
{
    public function up()
    {
        if ( ! $this->db->field_exists('gallery', 'pages'))
        {
            $this->db->query("ALTER TABLE pages ADD COLUMN gallery TEXT NULL COMMENT 'JSON [{image, caption}] — bộ sưu tập ảnh cuối trang' AFTER banner");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('gallery', 'pages'))
        {
            $this->db->query('ALTER TABLE pages DROP COLUMN gallery');
        }
    }
}
