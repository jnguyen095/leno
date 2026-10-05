<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * STOCKTAKER — nhân viên đi kiểm kho. Chỉ có quyền vào Kiểm kho (stock/adjust)
 * và xem Lịch sử nhập/xuất (stock/history), không được tự Nhập/Xuất kho.
 */
class Migration_Add_stocktaker_role extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE users MODIFY role ENUM('STAFF','BARISTA','CASHIER','ADMIN','BOOKING','STOCKTAKER') NOT NULL DEFAULT 'STAFF'");
    }

    public function down()
    {
        $this->db->query("UPDATE users SET role = 'STAFF' WHERE role = 'STOCKTAKER'");
        $this->db->query("ALTER TABLE users MODIFY role ENUM('STAFF','BARISTA','CASHIER','ADMIN','BOOKING') NOT NULL DEFAULT 'STAFF'");
    }
}
