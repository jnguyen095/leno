<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Reports.php cũ đã bị xóa (thay bằng Revenue_report) — cập nhật lại menu_items cho khớp controller mới. */
class Migration_Update_reports_menu_item extends CI_Migration
{
    public function up()
    {
        $this->db->where('menu_key', 'admin.reports')->update('menu_items', array(
            'label'      => 'Báo cáo doanh thu',
            'controller' => 'revenue_report',
            'methods'    => NULL,
            'route'      => 'reports',
        ));
    }

    public function down()
    {
        $this->db->where('menu_key', 'admin.reports')->update('menu_items', array(
            'label'      => 'Báo cáo',
            'controller' => 'reports',
            'methods'    => NULL,
            'route'      => 'reports',
        ));
    }
}
