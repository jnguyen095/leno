<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_order_items_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'order_session_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'product_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'qty' => array('type' => 'INT', 'constraint' => 5, 'default' => 1),
            'price' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'amount' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'note' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'CANCELLED'), 'default' => 'ACTIVE'),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('order_items', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE order_items ADD KEY idx_oi_order (order_session_id)');
        $this->db->query('ALTER TABLE order_items ADD KEY idx_oi_product (product_id)');
        $this->db->query('ALTER TABLE order_items ADD CONSTRAINT fk_oi_order FOREIGN KEY (order_session_id) REFERENCES order_sessions(id)');
        $this->db->query('ALTER TABLE order_items ADD CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('order_items', TRUE);
    }
}
