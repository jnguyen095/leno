<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Mỗi lượt đăng ký có 1 link riêng (theo uuid) để phụ huynh chia sẻ lên mạng xã hội. */
class Migration_Add_uuid_to_trung_thu_registrations extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE trung_thu_registrations ADD COLUMN uuid CHAR(32) NULL AFTER id');

        // Backfill uuid cho các dòng đã có sẵn (nếu có) trước khi bắt buộc NOT NULL + UNIQUE.
        $rows = $this->db->select('id')->get('trung_thu_registrations')->result_array();
        foreach ($rows as $row)
        {
            $this->db->where('id', $row['id'])->update('trung_thu_registrations', array('uuid' => bin2hex(random_bytes(16))));
        }

        $this->db->query('ALTER TABLE trung_thu_registrations MODIFY COLUMN uuid CHAR(32) NOT NULL');
        $this->db->query('ALTER TABLE trung_thu_registrations ADD UNIQUE KEY uq_ttr_uuid (uuid)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE trung_thu_registrations DROP INDEX uq_ttr_uuid');
        $this->db->query('ALTER TABLE trung_thu_registrations DROP COLUMN uuid');
    }
}
