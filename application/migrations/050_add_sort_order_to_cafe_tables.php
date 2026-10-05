<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cho phép admin sắp xếp thứ tự hiển thị bàn/sân (dropdown đặt lịch, sơ đồ bàn...) thay vì luôn theo mã bàn. */
class Migration_Add_sort_order_to_cafe_tables extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN sort_order INT(5) NOT NULL DEFAULT 0 AFTER table_name');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN sort_order');
    }
}
