<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thành phẩm (yield) + cách làm cho công thức. yield_quantity/yield_unit là
 * số lượng thành phẩm ra sau khi làm xong 1 mẻ (VD: mẻ "Trà Lài" ra
 * 1700ml) — cần có để công thức này có thể dùng làm 1 THÀNH PHẦN trong
 * công thức khác (tính giá theo (tổng cost mẻ / yield_quantity) * số lượng dùng).
 */
class Migration_Add_yield_to_recipes extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('recipes', array(
            'yield_quantity' => array('type' => 'DECIMAL', 'constraint' => '12,3', 'null' => TRUE, 'after' => 'name'),
            'yield_unit'     => array('type' => 'VARCHAR', 'constraint' => 10, 'null' => TRUE, 'after' => 'yield_quantity'),
            'instructions'   => array('type' => 'TEXT', 'null' => TRUE, 'after' => 'yield_unit'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('recipes', 'instructions');
        $this->dbforge->drop_column('recipes', 'yield_unit');
        $this->dbforge->drop_column('recipes', 'yield_quantity');
    }
}
