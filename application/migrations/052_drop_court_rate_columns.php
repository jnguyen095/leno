<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Giá tiền sân giờ cấu hình chung ở court_time_slots (xem migration 051) — không còn cần giá riêng từng sân. */
class Migration_Drop_court_rate_columns extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN rate_morning');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN rate_afternoon');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN rate_evening');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN rate_morning DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN rate_afternoon DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN rate_evening DECIMAL(12,2) NOT NULL DEFAULT 0');
    }
}
