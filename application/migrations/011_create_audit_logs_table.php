<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_audit_logs_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'module' => array('type' => 'VARCHAR', 'constraint' => 50),
            'action' => array('type' => 'VARCHAR', 'constraint' => 50),
            'old_data' => array('type' => 'TEXT', 'null' => TRUE),
            'new_data' => array('type' => 'TEXT', 'null' => TRUE),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('audit_logs', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE audit_logs ADD KEY idx_al_module (module)');
        $this->db->query('ALTER TABLE audit_logs ADD KEY idx_al_user (user_id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('audit_logs', TRUE);
    }
}
