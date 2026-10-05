<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Từ nay bàn chỉ "Đang phục vụ" khi đơn của bàn có ít nhất 1 món (Order_model::sync_table_status()).
 * Sửa dữ liệu cũ: bàn đang khác "Trống" mà không có đơn đang mở, hoặc đơn đang mở chưa có món nào
 * -> về "Trống". Đơn rỗng vẫn để mở; chọn lại bàn sẽ dùng tiếp đơn đó (Tables::open).
 */
class Migration_Reset_empty_tables_to_available extends CI_Migration
{
    public function up()
    {
        $this->db->query("
            UPDATE cafe_tables t
            SET t.status = 'AVAILABLE'
            WHERE t.status <> 'AVAILABLE'
              AND NOT EXISTS (
                  SELECT 1
                  FROM table_sessions ts
                  JOIN order_sessions o ON o.table_session_id = ts.id AND o.status IN ('OPEN', 'WAIT_PAYMENT')
                  JOIN order_items oi ON oi.order_session_id = o.id AND oi.status = 'ACTIVE'
                  WHERE ts.table_id = t.id AND ts.status = 'OPEN'
              )
        ");
    }

    public function down()
    {
        // Chỉ sửa trạng thái hiển thị của bàn — không có gì để hoàn tác.
    }
}
