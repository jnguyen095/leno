<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Đăng ký nhận quà Trung Thu — phụ huynh quét QR điền form công khai, không cần đăng nhập. */
class Migration_Create_trung_thu_registrations_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'          => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'phone'       => array('type' => 'VARCHAR', 'constraint' => 20),
            'parent_name' => array('type' => 'VARCHAR', 'constraint' => 150),
            'kid_count'   => array('type' => 'INT', 'constraint' => 3, 'unsigned' => TRUE, 'default' => 1),
            'created_at'  => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('trung_thu_registrations', TRUE, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->dbforge->drop_table('trung_thu_registrations', TRUE);
    }
}
