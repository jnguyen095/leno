<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_users_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'username' => array('type' => 'VARCHAR', 'constraint' => 100),
            'password' => array('type' => 'VARCHAR', 'constraint' => 255),
            'fullname' => array('type' => 'VARCHAR', 'constraint' => 150),
            'role' => array('type' => 'ENUM', 'constraint' => array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN'), 'default' => 'STAFF'),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('users', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE users ADD UNIQUE KEY uk_users_username (username)');
    }

    public function down()
    {
        $this->dbforge->drop_table('users', TRUE);
    }
}
