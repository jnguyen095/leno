<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Courts now price by time-of-day slot instead of one flat hourly_rate:
 * Sáng (06:00-12:00) / Chiều (12:00-18:00) / Tối (18:00-23:00). Existing
 * hourly_rate is backfilled into all three so already-configured courts
 * (e.g. T13-T15) keep working until an admin adjusts them per-slot.
 */
class Migration_Court_slot_pricing extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN rate_morning DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER hourly_rate');
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN rate_afternoon DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER rate_morning');
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN rate_evening DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER rate_afternoon');

        $this->db->query('UPDATE cafe_tables SET rate_morning = hourly_rate, rate_afternoon = hourly_rate, rate_evening = hourly_rate WHERE hourly_rate > 0');

        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN hourly_rate');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER table_type');
        $this->db->query('UPDATE cafe_tables SET hourly_rate = rate_morning');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN rate_morning');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN rate_afternoon');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN rate_evening');
    }
}
