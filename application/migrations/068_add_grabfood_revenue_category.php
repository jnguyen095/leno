<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thêm danh mục doanh thu "Grabfood" bên cạnh Khu Vui Chơi/Nước & Đồ Ăn/Pickleball/Photobooth. */
class Migration_Add_grabfood_revenue_category extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE monthly_revenue MODIFY category ENUM('KHU_VUI_CHOI','NUOC_DO_AN','PICKLEBALL','PHOTOBOOTH','GRABFOOD') NOT NULL");
    }

    public function down()
    {
        $this->db->query("DELETE FROM monthly_revenue WHERE category = 'GRABFOOD'");
        $this->db->query("ALTER TABLE monthly_revenue MODIFY category ENUM('KHU_VUI_CHOI','NUOC_DO_AN','PICKLEBALL','PHOTOBOOTH') NOT NULL");
    }
}
