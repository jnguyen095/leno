<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nhật ký ghi chú cho lịch đặt sân — mỗi dòng là 1 ghi chú được thêm dần
 * theo thời gian (không ghi đè). Với lịch lặp lại, ghi chú gắn vào
 * booking_group_id nên hiện ra ở mọi buổi trong cùng chuỗi; với lịch đặt
 * đơn lẻ (không có group), ghi chú gắn trực tiếp vào booking_id.
 */
class Migration_Create_court_booking_notes_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'               => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'booking_id'       => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'booking_group_id' => array('type' => 'CHAR', 'constraint' => 32, 'null' => TRUE),
            'note'             => array('type' => 'VARCHAR', 'constraint' => 500),
            'created_by'       => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at'       => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('court_booking_notes', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE court_booking_notes ADD KEY idx_cbn_booking (booking_id)');
        $this->db->query('ALTER TABLE court_booking_notes ADD KEY idx_cbn_group (booking_group_id)');
        $this->db->query('ALTER TABLE court_booking_notes ADD CONSTRAINT fk_cbn_booking FOREIGN KEY (booking_id) REFERENCES court_bookings(id)');
        $this->db->query('ALTER TABLE court_booking_notes ADD CONSTRAINT fk_cbn_user FOREIGN KEY (created_by) REFERENCES users(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('court_booking_notes', TRUE);
    }
}
