<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Điểm xuất kho — nơi vật tư được đưa tới khi xuất kho (Bar, Pickleball, Bếp).
 * Là bảng CRUD được (không hard-code enum) để ADMIN tự thêm/sửa sau này.
 */
class Migration_Create_dispense_points_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name' => array('type' => 'VARCHAR', 'constraint' => 100),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('dispense_points', TRUE, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->dbforge->drop_table('dispense_points', TRUE);
    }
}
