<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_table_sessions_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'table_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'session_no' => array('type' => 'VARCHAR', 'constraint' => 30),
            'opened_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'opened_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'closed_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'status' => array('type' => 'ENUM', 'constraint' => array('OPEN', 'CLOSED'), 'default' => 'OPEN'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('table_sessions', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE table_sessions ADD KEY idx_ts_table (table_id)');
        $this->db->query('ALTER TABLE table_sessions ADD KEY idx_ts_status (status)');
        $this->db->query('ALTER TABLE table_sessions ADD CONSTRAINT fk_ts_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id)');
        $this->db->query('ALTER TABLE table_sessions ADD CONSTRAINT fk_ts_user FOREIGN KEY (opened_by) REFERENCES users(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('table_sessions', TRUE);
    }
}
