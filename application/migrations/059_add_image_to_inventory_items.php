<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ảnh sản phẩm kho — giúp nhân viên nhận diện hàng nhanh hơn khi nhập/xuất/kiểm kho. */
class Migration_Add_image_to_inventory_items extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('inventory_items', array(
            'image' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE, 'after' => 'name'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('inventory_items', 'image');
    }
}
