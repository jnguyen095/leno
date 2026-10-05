<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ngày bắt đầu/kết thúc làm việc — dùng để giới hạn khoảng ngày được nhập giờ làm trong module Lương. */
class Migration_Add_employment_dates_to_users extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('users', array(
            'start_date' => array('type' => 'DATE', 'null' => TRUE, 'after' => 'role'),
            'end_date'   => array('type' => 'DATE', 'null' => TRUE, 'after' => 'start_date'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('users', 'end_date');
        $this->dbforge->drop_column('users', 'start_date');
    }
}
