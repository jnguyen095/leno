<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thêm loại giao dịch ADJUST (điều chỉnh từ kiểm kho) vào stock_transactions. */
class Migration_Add_adjust_type_to_stock_transactions extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE stock_transactions MODIFY type ENUM('IN','OUT','ADJUST') NOT NULL");
    }

    public function down()
    {
        $this->db->query("DELETE FROM stock_transactions WHERE type = 'ADJUST'");
        $this->db->query("ALTER TABLE stock_transactions MODIFY type ENUM('IN','OUT') NOT NULL");
    }
}
