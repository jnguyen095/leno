<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Mỗi số điện thoại chỉ đăng ký nhận quà Trung Thu 1 lần. */
class Migration_Add_unique_phone_to_trung_thu_registrations extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE trung_thu_registrations ADD UNIQUE KEY uq_ttr_phone (phone)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE trung_thu_registrations DROP INDEX uq_ttr_phone');
    }
}
