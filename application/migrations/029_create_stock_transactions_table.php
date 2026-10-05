<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sổ cái nhập/xuất kho — mỗi dòng là 1 lần nhập (IN) hoặc xuất (OUT). Dùng
 * cho lịch sử/audit; tồn kho hiện tại được cache ở inventory_items.qty_on_hand.
 */
class Migration_Create_stock_transactions_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'type' => array('type' => 'ENUM', 'constraint' => array('IN', 'OUT')),
            'qty' => array('type' => 'DECIMAL', 'constraint' => '12,2'),
            'dispense_point_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'source' => array('type' => 'ENUM', 'constraint' => array('MANUAL', 'EXCEL'), 'default' => 'MANUAL'),
            'note' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('stock_transactions', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE stock_transactions ADD KEY idx_st_item (item_id)');
        $this->db->query('ALTER TABLE stock_transactions ADD KEY idx_st_dispense_point (dispense_point_id)');
        $this->db->query('ALTER TABLE stock_transactions ADD KEY idx_st_created_by (created_by)');
        $this->db->query('ALTER TABLE stock_transactions ADD CONSTRAINT fk_st_item FOREIGN KEY (item_id) REFERENCES inventory_items(id)');
        $this->db->query('ALTER TABLE stock_transactions ADD CONSTRAINT fk_st_dispense_point FOREIGN KEY (dispense_point_id) REFERENCES dispense_points(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('stock_transactions', TRUE);
    }
}
