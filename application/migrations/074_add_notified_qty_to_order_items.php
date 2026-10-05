<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Món thêm vào đơn không còn tự gửi bếp — chỉ gửi khi nhân viên bấm "Thông báo".
 * order_items.notified_qty = số lượng đã báo bếp của dòng món đó; "Thông báo" gửi phần
 * chênh lệch (qty hiện tại − notified_qty): dương = món mới/thêm, âm = món bị bớt/hủy.
 *
 * Dữ liệu cũ: món ACTIVE đều đã lên bếp ngay lúc thêm (luồng cũ) -> notified_qty = qty;
 * món đã hủy coi như đã xử lý xong -> 0 (không in phiếu hủy cho đơn cũ).
 */
class Migration_Add_notified_qty_to_order_items extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE order_items ADD COLUMN notified_qty INT NOT NULL DEFAULT 0 AFTER qty');
        $this->db->query("UPDATE order_items SET notified_qty = qty WHERE status = 'ACTIVE'");
    }

    public function down()
    {
        $this->db->query('ALTER TABLE order_items DROP COLUMN notified_qty');
    }
}
