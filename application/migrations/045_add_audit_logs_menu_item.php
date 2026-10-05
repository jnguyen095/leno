<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thêm "Nhật ký hệ thống" vào danh mục menu gán quyền động — chỉ ADMIN mặc định. */
class Migration_Add_audit_logs_menu_item extends CI_Migration
{
    public function up()
    {
        $this->db->insert('menu_items', array(
            'group_label' => 'Quản trị',
            'menu_key'    => 'admin.audit_logs',
            'label'       => 'Nhật ký hệ thống',
            'controller'  => 'audit_logs',
            'methods'     => NULL,
            'route'       => 'audit-logs',
            'sort_order'  => 275,
        ));
        $menu_item_id = $this->db->insert_id();
        $this->db->insert('role_menu_permissions', array('role' => 'ADMIN', 'menu_item_id' => $menu_item_id));
    }

    public function down()
    {
        $item = $this->db->where('menu_key', 'admin.audit_logs')->get('menu_items')->row_array();
        if ($item)
        {
            $this->db->where('menu_item_id', $item['id'])->delete('role_menu_permissions');
            $this->db->where('menu_item_id', $item['id'])->delete('user_menu_permissions');
            $this->db->where('id', $item['id'])->delete('menu_items');
        }
    }
}
