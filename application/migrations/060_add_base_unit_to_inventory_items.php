<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Đơn vị cơ sở (ml/gram) + giá mỗi đơn vị cơ sở — dùng để tính cost nguyên
 * liệu trong công thức pha chế (xem 061/062_*recipes*). Ví dụ: "Sinh Tố Ổi
 * Hồng OHLA 1000ML/Chai" nhập vào base_unit='ml', base_unit_cost=200 (đ/ml).
 * Không bắt buộc — chỉ cần cho các nguyên liệu dùng trong công thức.
 */
class Migration_Add_base_unit_to_inventory_items extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('inventory_items', array(
            'base_unit'      => array('type' => 'VARCHAR', 'constraint' => 10, 'null' => TRUE, 'after' => 'unit_id'),
            'base_unit_cost' => array('type' => 'DECIMAL', 'constraint' => '12,4', 'null' => TRUE, 'after' => 'base_unit'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('inventory_items', 'base_unit_cost');
        $this->dbforge->drop_column('inventory_items', 'base_unit');
    }
}
