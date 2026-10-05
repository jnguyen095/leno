<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bỏ hẳn tính năng khách quét QR trên bàn để xem menu/gọi món/gọi nhân viên:
 *  - cafe_tables.qr_token (mã in trên QR), table_sessions.session_secret (link theo lượt khách)
 *  - bảng assistance_calls (khách bấm "Gọi nhân viên"/"Yêu cầu thanh toán" từ menu QR)
 *    cùng nhật ký hệ thống của module này.
 * Bàn giờ chỉ do nhân viên mở từ Sơ đồ bàn. Không rollback được dữ liệu — khôi phục từ bản sao lưu DB.
 */
class Migration_Remove_qr_ordering extends CI_Migration
{
    public function up()
    {
        $this->dbforge->drop_table('assistance_calls', TRUE);
        $this->db->where('module', 'assistance_call')->delete('audit_logs');

        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN qr_token');
        $this->db->query('ALTER TABLE table_sessions DROP COLUMN session_secret');
    }

    public function down()
    {
        show_error('Migration 073 (bỏ gọi món QR) không rollback được — khôi phục từ bản sao lưu DB.');
    }
}
