<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_kitchen_tickets_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'order_session_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'table_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'status' => array('type' => 'ENUM', 'constraint' => array('NEW', 'PREPARING', 'COMPLETED'), 'default' => 'NEW'),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('kitchen_tickets', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE kitchen_tickets ADD KEY idx_kt_order (order_session_id)');
        $this->db->query('ALTER TABLE kitchen_tickets ADD KEY idx_kt_table (table_id)');
        $this->db->query('ALTER TABLE kitchen_tickets ADD KEY idx_kt_status (status)');
        $this->db->query('ALTER TABLE kitchen_tickets ADD CONSTRAINT fk_kt_order FOREIGN KEY (order_session_id) REFERENCES order_sessions(id)');
        $this->db->query('ALTER TABLE kitchen_tickets ADD CONSTRAINT fk_kt_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('kitchen_tickets', TRUE);
    }
}
