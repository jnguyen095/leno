<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Đơn vị tính cho sản phẩm kho (cái, kg, lít, lon...) — quản lý được (thêm/sửa/xoá), không hard-code. */
class Migration_Create_inventory_units_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name' => array('type' => 'VARCHAR', 'constraint' => 30),
            'status' => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('inventory_units', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE inventory_units ADD UNIQUE KEY uk_inventory_units_name (name)');

        $this->db->insert_batch('inventory_units', array(
            array('name' => 'Cái', 'status' => 'ACTIVE'),
            array('name' => 'Kg', 'status' => 'ACTIVE'),
            array('name' => 'Lít', 'status' => 'ACTIVE'),
            array('name' => 'Lon', 'status' => 'ACTIVE'),
            array('name' => 'Chai', 'status' => 'ACTIVE'),
            array('name' => 'Gói', 'status' => 'ACTIVE'),
            array('name' => 'Hộp', 'status' => 'ACTIVE'),
            array('name' => 'Thùng', 'status' => 'ACTIVE'),
            array('name' => 'Bó', 'status' => 'ACTIVE'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_table('inventory_units', TRUE);
    }
}
