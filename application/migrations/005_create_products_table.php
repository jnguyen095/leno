<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_products_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'sku' => array('type' => 'VARCHAR', 'constraint' => 30),
            'product_name' => array('type' => 'VARCHAR', 'constraint' => 150),
            'price' => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'image' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'description' => array('type' => 'TEXT', 'null' => TRUE),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('products', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE products ADD UNIQUE KEY uk_products_sku (sku)');
        $this->db->query('ALTER TABLE products ADD KEY idx_products_category (category_id)');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('products', TRUE);
    }
}
