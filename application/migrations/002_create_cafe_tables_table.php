<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_cafe_tables_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'table_code' => array('type' => 'VARCHAR', 'constraint' => 20),
            'table_name' => array('type' => 'VARCHAR', 'constraint' => 100),
            'capacity' => array('type' => 'INT', 'constraint' => 3, 'default' => 4),
            'qr_token' => array('type' => 'CHAR', 'constraint' => 32),
            'status' => array('type' => 'ENUM', 'constraint' => array('AVAILABLE', 'OPEN', 'WAIT_PAYMENT', 'PAID'), 'default' => 'AVAILABLE'),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('cafe_tables', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE cafe_tables ADD UNIQUE KEY uk_tables_code (table_code)');
        $this->db->query('ALTER TABLE cafe_tables ADD UNIQUE KEY uk_tables_token (qr_token)');
    }

    public function down()
    {
        $this->dbforge->drop_table('cafe_tables', TRUE);
    }
}
