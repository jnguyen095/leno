<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_kitchen_ticket_items_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'ticket_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'product_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'qty' => array('type' => 'INT', 'constraint' => 5, 'default' => 1),
            'note' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'status' => array('type' => 'ENUM', 'constraint' => array('NEW', 'PREPARING', 'COMPLETED'), 'default' => 'NEW'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('kitchen_ticket_items', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE kitchen_ticket_items ADD KEY idx_kti_ticket (ticket_id)');
        $this->db->query('ALTER TABLE kitchen_ticket_items ADD KEY idx_kti_product (product_id)');
        $this->db->query('ALTER TABLE kitchen_ticket_items ADD CONSTRAINT fk_kti_ticket FOREIGN KEY (ticket_id) REFERENCES kitchen_tickets(id)');
        $this->db->query('ALTER TABLE kitchen_ticket_items ADD CONSTRAINT fk_kti_product FOREIGN KEY (product_id) REFERENCES products(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('kitchen_ticket_items', TRUE);
    }
}
