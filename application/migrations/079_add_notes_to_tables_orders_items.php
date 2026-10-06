<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ghi chú 3 cấp:
 *  - cafe_tables.note: ghi chú cố định của bàn (vd "Ghế hỏng 1 cái"), giữ qua các lượt khách.
 *  - order_sessions.note: ghi chú cho cả đơn (vd "Khách VIP"), in trên phiếu bếp / tạm tính.
 *  - order_items.note đã có; thêm notified_note = ghi chú bếp đã nhận ở lần "Thông báo" gần nhất,
 *    để sửa ghi chú món đã báo bếp thì lần Thông báo sau in mục "ĐỔI GHI CHÚ".
 */
class Migration_Add_notes_to_tables_orders_items extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE cafe_tables ADD COLUMN note VARCHAR(255) NULL AFTER table_name');
        $this->db->query('ALTER TABLE order_sessions ADD COLUMN note VARCHAR(255) NULL AFTER status');
        $this->db->query('ALTER TABLE order_items ADD COLUMN notified_note VARCHAR(255) NULL AFTER note');
        // Món đã báo bếp: coi như bếp đã có đúng ghi chú hiện tại.
        $this->db->query('UPDATE order_items SET notified_note = note WHERE notified_qty > 0');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE order_items DROP COLUMN notified_note');
        $this->db->query('ALTER TABLE order_sessions DROP COLUMN note');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN note');
    }
}
