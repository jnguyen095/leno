<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lưu người tạo đơn (nhân viên đăng nhập mở bàn) để hiện ở danh sách Đơn hàng.
 * Dữ liệu cũ: lấy từ table_sessions.opened_by; đơn mang đi kiểu cũ (không có bàn)
 * lấy từ nhật ký CREATE_TAKEAWAY nếu còn.
 */
class Migration_Add_created_by_to_order_sessions extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE order_sessions ADD COLUMN created_by INT UNSIGNED NULL AFTER status, ADD KEY idx_order_sessions_created_by (created_by)');

        $this->db->query('
            UPDATE order_sessions o
            JOIN table_sessions ts ON ts.id = o.table_session_id
            SET o.created_by = ts.opened_by
            WHERE o.created_by IS NULL
        ');
        $this->db->query("
            UPDATE order_sessions o
            JOIN audit_logs al ON al.action = 'CREATE_TAKEAWAY'
                AND CAST(JSON_VALUE(al.new_data, '$.order_id') AS UNSIGNED) = o.id
            SET o.created_by = al.user_id
            WHERE o.created_by IS NULL
        ");
    }

    public function down()
    {
        $this->db->query('ALTER TABLE order_sessions DROP KEY idx_order_sessions_created_by, DROP COLUMN created_by');
    }
}
