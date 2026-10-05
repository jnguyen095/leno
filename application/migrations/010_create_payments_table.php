<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_payments_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'order_session_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'payment_method' => array('type' => 'ENUM', 'constraint' => array('CASH', 'CARD', 'TRANSFER', 'QR'), 'default' => 'CASH'),
            'amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'received_amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'change_amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'paid_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'paid_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payments', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payments ADD KEY idx_pay_order (order_session_id)');
        $this->db->query('ALTER TABLE payments ADD CONSTRAINT fk_pay_order FOREIGN KEY (order_session_id) REFERENCES order_sessions(id)');
        $this->db->query('ALTER TABLE payments ADD CONSTRAINT fk_pay_user FOREIGN KEY (paid_by) REFERENCES users(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('payments', TRUE);
    }
}
