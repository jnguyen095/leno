<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Danh mục kho vật tư (Nhà Bếp, Pha Chế, Pickleball, Ly & Ống Hút, Nước Ngọt,
 * Bim Bim...) — tách riêng khỏi `categories` (danh mục thực đơn bán hàng).
 */
class Migration_Create_inventory_categories_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name' => array('type' => 'VARCHAR', 'constraint' => 100),
            'sort_order' => array('type' => 'INT', 'constraint' => 5, 'default' => 0),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('inventory_categories', TRUE, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->dbforge->drop_table('inventory_categories', TRUE);
    }
}
