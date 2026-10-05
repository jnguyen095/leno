<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_order_sessions_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'order_no' => array('type' => 'VARCHAR', 'constraint' => 30),
            'table_session_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'status' => array('type' => 'ENUM', 'constraint' => array('OPEN', 'WAIT_PAYMENT', 'PAID', 'CANCELLED'), 'default' => 'OPEN'),
            'subtotal' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'discount_amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'vat_amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'total_amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'paid_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('order_sessions', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE order_sessions ADD UNIQUE KEY uk_order_no (order_no)');
        $this->db->query('ALTER TABLE order_sessions ADD KEY idx_os_table_session (table_session_id)');
        $this->db->query('ALTER TABLE order_sessions ADD CONSTRAINT fk_os_table_session FOREIGN KEY (table_session_id) REFERENCES table_sessions(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('order_sessions', TRUE);
    }
}
