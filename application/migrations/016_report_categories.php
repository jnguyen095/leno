<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Báo cáo danh mục (me/reports/categories): thêm mục menu trong nhóm "Báo cáo" để gán quyền riêng
 * (Quản trị → Gán quyền menu). Cách thêm / gỡ giống migration 015.
 */
class Migration_Report_categories extends CI_Migration
{
    const ITEMS = array(
        'reports.categories' => array('Báo cáo danh mục', 'categories,categories_export', 310),
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
