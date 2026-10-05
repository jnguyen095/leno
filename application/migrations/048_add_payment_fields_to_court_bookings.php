<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cho phép đánh dấu 1 buổi đặt sân đã thanh toán (thu tiền mặt/chuyển khoản
 * trước, tách biệt với dòng "Tiền sân" trong order lúc check-in) kèm số
 * order/mã tham chiếu và số tiền đã thu.
 */
class Migration_Add_payment_fields_to_court_bookings extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE court_bookings ADD COLUMN is_paid ENUM('YES','NO') NOT NULL DEFAULT 'NO' AFTER notes");
        $this->db->query("ALTER TABLE court_bookings ADD COLUMN payment_order_no VARCHAR(100) NULL AFTER is_paid");
        $this->db->query("ALTER TABLE court_bookings ADD COLUMN payment_amount DECIMAL(12,2) NULL AFTER payment_order_no");
    }

    public function down()
    {
        $this->db->query('ALTER TABLE court_bookings DROP COLUMN payment_amount');
        $this->db->query('ALTER TABLE court_bookings DROP COLUMN payment_order_no');
        $this->db->query('ALTER TABLE court_bookings DROP COLUMN is_paid');
    }
}
