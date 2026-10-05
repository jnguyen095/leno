<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_assistance_calls_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'table_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'type' => array('type' => 'ENUM', 'constraint' => array('HELP', 'PAYMENT'), 'default' => 'HELP'),
            'status' => array('type' => 'ENUM', 'constraint' => array('PENDING', 'RESOLVED'), 'default' => 'PENDING'),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'resolved_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'resolved_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('assistance_calls', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE assistance_calls ADD KEY idx_ac_table (table_id)');
        $this->db->query('ALTER TABLE assistance_calls ADD KEY idx_ac_status (status)');
        $this->db->query('ALTER TABLE assistance_calls ADD CONSTRAINT fk_ac_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id)');
        $this->db->query('ALTER TABLE assistance_calls ADD CONSTRAINT fk_ac_user FOREIGN KEY (resolved_by) REFERENCES users(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('assistance_calls', TRUE);
    }
}
