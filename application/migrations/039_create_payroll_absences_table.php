<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ngày nghỉ của nhân viên lương cố định — admin đánh dấu từng ngày cụ thể
 * (thay vì gõ tổng số ngày nghỉ tay), số ngày nghỉ trong tháng được tính
 * bằng COUNT các dòng trong khoảng ngày của tháng đó. Cột
 * payroll_records.absence_days không còn dùng nữa (xem migration 040).
 */
class Migration_Create_payroll_absences_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'       => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'absence_date'  => array('type' => 'DATE'),
            'note'          => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'created_by'    => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at'    => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payroll_absences', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payroll_absences ADD UNIQUE KEY uq_pa_user_date (user_id, absence_date)');
    }

    public function down()
    {
        $this->dbforge->drop_table('payroll_absences', TRUE);
    }
}
