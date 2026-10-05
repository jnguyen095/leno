<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cho phép đánh dấu nghỉ cả ngày (1) hoặc nửa ngày (0.5) thay vì chỉ có 1 lựa chọn cả ngày. */
class Migration_Add_fraction_to_payroll_absences extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('payroll_absences', array(
            'fraction' => array('type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 1.00, 'after' => 'absence_date'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('payroll_absences', 'fraction');
    }
}
