<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sản phẩm/vật tư trong kho. qty_on_hand là số dư tồn kho được duy trì trực
 * tiếp (cập nhật mỗi khi có stock_transactions mới), không tính SUM mỗi lần
 * đọc — cùng cách tiếp cận với cafe_tables.status trong app này.
 */
class Migration_Create_inventory_items_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'sku' => array('type' => 'VARCHAR', 'constraint' => 30),
            'name' => array('type' => 'VARCHAR', 'constraint' => 150),
            'unit' => array('type' => 'VARCHAR', 'constraint' => 30),
            'storage_type' => array('type' => 'ENUM', 'constraint' => array('COLD', 'DRY'), 'default' => 'DRY'),
            'low_stock_threshold' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'qty_on_hand' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('inventory_items', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE inventory_items ADD UNIQUE KEY uk_inventory_items_sku (sku)');
        $this->db->query('ALTER TABLE inventory_items ADD KEY idx_inventory_items_category (category_id)');
        $this->db->query('ALTER TABLE inventory_items ADD CONSTRAINT fk_inventory_items_category FOREIGN KEY (category_id) REFERENCES inventory_categories(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('inventory_items', TRUE);
    }
}
