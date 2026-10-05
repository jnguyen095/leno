<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Module Lương — 3 bảng:
 *  - payroll_settings: cấu hình lương của từng nhân viên (loại lương, mức
 *    lương, thông tin ngân hàng) — admin quản lý, 1 dòng/nhân viên.
 *  - payroll_records: dữ liệu theo từng tháng (số ngày nghỉ cho lương cố
 *    định, số tiền đã ứng, đã chi lương chưa) — 1 dòng/nhân viên/tháng.
 *  - payroll_hours: số giờ làm mỗi ngày cho nhân viên lương theo giờ — admin
 *    nhập, 1 dòng/nhân viên/ngày.
 */
class Migration_Create_payroll_tables extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'                  => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'             => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'salary_type'         => array('type' => 'ENUM', 'constraint' => array('FIXED', 'HOURLY'), 'default' => 'FIXED'),
            'fixed_salary'        => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'hourly_rate'         => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'bank_name'           => array('type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE),
            'bank_branch'         => array('type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE),
            'bank_account_number' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => TRUE),
            'bank_account_name'   => array('type' => 'VARCHAR', 'constraint' => 150, 'null' => TRUE),
            'updated_at'          => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payroll_settings', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payroll_settings ADD UNIQUE KEY uq_ps_user (user_id)');

        $this->dbforge->add_field(array(
            'id'              => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'period'          => array('type' => 'CHAR', 'constraint' => 7), // 'YYYY-MM'
            'absence_days'    => array('type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0),
            'advance_amount'  => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'paid_status'     => array('type' => 'ENUM', 'constraint' => array('UNPAID', 'PAID'), 'default' => 'UNPAID'),
            'paid_at'         => array('type' => 'DATETIME', 'null' => TRUE),
            'note'            => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'updated_by'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at'      => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at'      => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payroll_records', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payroll_records ADD UNIQUE KEY uq_pr_user_period (user_id, period)');

        $this->dbforge->add_field(array(
            'id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'    => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'work_date'  => array('type' => 'DATE'),
            'hours'      => array('type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0),
            'note'       => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payroll_hours', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payroll_hours ADD UNIQUE KEY uq_ph_user_date (user_id, work_date)');
    }

    public function down()
    {
        $this->dbforge->drop_table('payroll_hours', TRUE);
        $this->dbforge->drop_table('payroll_records', TRUE);
        $this->dbforge->drop_table('payroll_settings', TRUE);
    }
}
