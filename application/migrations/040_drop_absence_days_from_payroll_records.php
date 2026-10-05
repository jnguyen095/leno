<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** absence_days giờ được tính từ payroll_absences (đánh dấu từng ngày cụ thể) thay vì nhập tay 1 số tổng. */
class Migration_Drop_absence_days_from_payroll_records extends CI_Migration
{
    public function up()
    {
        $this->dbforge->drop_column('payroll_records', 'absence_days');
    }

    public function down()
    {
        $this->dbforge->add_column('payroll_records', array(
            'absence_days' => array('type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0, 'after' => 'period'),
        ));
    }
}
