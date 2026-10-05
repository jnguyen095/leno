<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * KDS giờ lọc ticket COMPLETED theo status + updated_at (chỉ lấy trong ngày) — thêm
 * composite index để query này chạy bằng index scan thay vì lọc sau khi quét theo status.
 */
class Migration_Add_kitchen_tickets_status_updated_index extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE kitchen_tickets ADD KEY idx_kt_status_updated (status, updated_at)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE kitchen_tickets DROP KEY idx_kt_status_updated');
    }
}
