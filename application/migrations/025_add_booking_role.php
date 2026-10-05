<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BOOKING — vai trò lễ tân chỉ nhận đặt sân qua điện thoại, không đụng vào
 * bàn/đơn hàng/bếp/thu ngân. Chỉ có quyền vào module Lịch sân (Bookings).
 */
class Migration_Add_booking_role extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE users MODIFY role ENUM('STAFF','BARISTA','CASHIER','ADMIN','BOOKING') NOT NULL DEFAULT 'STAFF'");
    }

    public function down()
    {
        $this->db->query("UPDATE users SET role = 'STAFF' WHERE role = 'BOOKING'");
        $this->db->query("ALTER TABLE users MODIFY role ENUM('STAFF','BARISTA','CASHIER','ADMIN') NOT NULL DEFAULT 'STAFF'");
    }
}
