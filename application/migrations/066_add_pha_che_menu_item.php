<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thêm "Pha chế" (xem công thức không kèm cost — Pha_che.php, khác hẳn
 * Recipes.php của ADMIN) vào danh mục menu gán quyền động — mặc định chỉ
 * ADMIN thấy, admin tự cấp thêm cho vai trò khác (VD: BARISTA) qua
 * /menu-permissions.
 */
class Migration_Add_pha_che_menu_item extends CI_Migration
{
    public function up()
    {
        $this->db->insert('menu_items', array(
            'group_label' => NULL,
            'menu_key'    => 'pha_che',
            'label'       => 'Pha chế',
            'controller'  => 'pha_che',
            'methods'     => NULL,
            'route'       => 'pha-che',
            'sort_order'  => 55,
        ));
        $menu_item_id = $this->db->insert_id();
        $this->db->insert('role_menu_permissions', array('role' => 'ADMIN', 'menu_item_id' => $menu_item_id));
    }

    public function down()
    {
        $item = $this->db->where('menu_key', 'pha_che')->get('menu_items')->row_array();
        if ($item)
        {
            $this->db->where('menu_item_id', $item['id'])->delete('role_menu_permissions');
            $this->db->where('menu_item_id', $item['id'])->delete('user_menu_permissions');
            $this->db->where('id', $item['id'])->delete('menu_items');
        }
    }
}
