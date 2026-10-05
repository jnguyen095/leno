<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khung giờ + giá tiền sân do admin tự cấu hình (VD: "Khung 1: 06:00-13:00,
 * 80.000đ/giờ") — dùng chung cho mọi sân, thay cho 3 khung Sáng/Chiều/Tối
 * cố định trong code trước đây (Court_booking_model::SLOTS) và giá riêng
 * từng sân (cafe_tables.rate_morning/afternoon/evening, xem migration 052).
 * Seed lại đúng 3 khung + giá đang áp dụng để không đổi giá ngay sau khi
 * chạy migration — admin có thể sửa/thêm/xóa khung sau đó.
 */
class Migration_Create_court_time_slots_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'             => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'label'          => array('type' => 'VARCHAR', 'constraint' => 100),
            'start_time'     => array('type' => 'TIME'),
            'end_time'       => array('type' => 'TIME'),
            'price_per_hour' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'sort_order'     => array('type' => 'INT', 'constraint' => 5, 'default' => 0),
            'status'         => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
            'created_at'     => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at'     => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('court_time_slots', TRUE, array('ENGINE' => 'InnoDB'));

        $now = date('Y-m-d H:i:s');
        $this->db->insert_batch('court_time_slots', array(
            array('label' => 'Sáng',  'start_time' => '01:00:00', 'end_time' => '13:00:00', 'price_per_hour' => 50000, 'sort_order' => 1, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
            array('label' => 'Chiều', 'start_time' => '13:00:00', 'end_time' => '17:00:00', 'price_per_hour' => 80000, 'sort_order' => 2, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
            array('label' => 'Tối',   'start_time' => '17:00:00', 'end_time' => '23:00:00', 'price_per_hour' => 100000, 'sort_order' => 3, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_table('court_time_slots', TRUE);
    }
}
