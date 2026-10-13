<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hình thức thanh toán chỉ còn Tiền mặt + Chuyển khoản (bỏ Thẻ, QR Pay) — khớp payment_methods() (kds_helper).
 * Bản ghi cũ là CARD / QR (nếu có) được tính là Chuyển khoản (đều là thanh toán không dùng tiền mặt) trước khi thu hẹp cột.
 */
class Migration_Payment_methods_cash_transfer extends CI_Migration
{
    public function up()
    {
        $this->db->query("UPDATE payments SET payment_method = 'TRANSFER' WHERE payment_method IN ('CARD', 'QR')");
        $this->db->query("ALTER TABLE payments MODIFY payment_method ENUM('CASH','TRANSFER') NOT NULL DEFAULT 'CASH'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE payments MODIFY payment_method ENUM('CASH','CARD','TRANSFER','QR') NOT NULL DEFAULT 'CASH'");
    }
}
