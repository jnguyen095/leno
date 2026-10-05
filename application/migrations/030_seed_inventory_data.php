<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Seed_inventory_data extends CI_Migration
{
    public function up()
    {
        $this->db->insert_batch('inventory_categories', array(
            array('name' => 'Nhà Bếp', 'sort_order' => 1, 'status' => 'ACTIVE'),
            array('name' => 'Pha Chế', 'sort_order' => 2, 'status' => 'ACTIVE'),
            array('name' => 'Pickleball', 'sort_order' => 3, 'status' => 'ACTIVE'),
            array('name' => 'Ly & Ống Hút', 'sort_order' => 4, 'status' => 'ACTIVE'),
            array('name' => 'Nước Ngọt', 'sort_order' => 5, 'status' => 'ACTIVE'),
            array('name' => 'Bim Bim', 'sort_order' => 6, 'status' => 'ACTIVE'),
        ));

        $this->db->insert_batch('dispense_points', array(
            array('name' => 'Bar', 'status' => 'ACTIVE'),
            array('name' => 'Pickleball', 'status' => 'ACTIVE'),
            array('name' => 'Bếp', 'status' => 'ACTIVE'),
        ));
    }

    public function down()
    {
        $this->db->truncate('dispense_points');
        $this->db->truncate('inventory_categories');
    }
}
