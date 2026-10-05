<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Giờ hoạt động nhận đặt sân (mặc định 06:00 - 22:00), cấu hình được ở /settings.
 */
class Migration_Seed_booking_hours_settings extends CI_Migration
{
    public function up()
    {
        $this->db->insert_batch('settings', array(
            array('setting_key' => 'booking_start_time', 'setting_value' => '06:00', 'updated_at' => date('Y-m-d H:i:s')),
            array('setting_key' => 'booking_end_time', 'setting_value' => '22:00', 'updated_at' => date('Y-m-d H:i:s')),
        ));
    }

    public function down()
    {
        $this->db->where_in('setting_key', array('booking_start_time', 'booking_end_time'))->delete('settings');
    }
}
