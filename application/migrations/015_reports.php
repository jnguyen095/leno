<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Báo cáo (menu "Báo cáo" trên thanh điều hướng — controllers/Reports.php). Mỗi báo cáo là một mục menu riêng trong
 * nhóm "Báo cáo" để quản trị gán quyền cho vai trò / từng người ở Quản trị → Gán quyền menu.
 * ADMIN luôn xem được mọi báo cáo.
 */
class Migration_Reports extends CI_Migration
{
    /** menu_key => [nhãn, methods của controller reports, thứ tự]. Thêm báo cáo mới: thêm dòng ở migration sau. */
    const ITEMS = array(
        'reports.products' => array('Báo cáo hàng hóa', 'products,products_export', 300),
    );

    public function up()
    {
        foreach (self::ITEMS as $key => $it)
        {
            if ($this->db->where('menu_key', $key)->count_all_results('menu_items')) continue;
            $this->db->insert('menu_items', array(
                'group_label' => 'Báo cáo', 'menu_key' => $key, 'label' => $it[0], 'icon' => NULL,
                'controller' => 'reports', 'methods' => $it[1], 'route' => 'reports/'.strtok($it[1], ','), 'sort_order' => $it[2],
            ));
        }
    }

    public function down()
    {
        foreach (array_keys(self::ITEMS) as $key)
        {
            $item = $this->db->where('menu_key', $key)->get('menu_items')->row_array();
            if ( ! $item) continue;
            $this->db->where('menu_item_id', $item['id'])->delete('role_menu_permissions');
            if ($this->db->table_exists('user_menu_permissions')) $this->db->where('menu_item_id', $item['id'])->delete('user_menu_permissions');
            $this->db->where('id', $item['id'])->delete('menu_items');
        }
    }
}
