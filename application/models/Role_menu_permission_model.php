<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Quyền menu mặc định theo vai trò. */
class Role_menu_permission_model extends CI_Model
{
    protected $table = 'role_menu_permissions';

    /** Mảng [menu_item_id => TRUE] các mục 1 vai trò đang được cấp. */
    public function get_menu_item_ids_for_role($role)
    {
        $rows = $this->db->select('menu_item_id')->where('role', $role)->get($this->table)->result_array();
        $ids = array();
        foreach ($rows as $r)
        {
            $ids[(int) $r['menu_item_id']] = TRUE;
        }
        return $ids;
    }

    public function role_has($role, $menu_item_id)
    {
        return $this->db->where('role', $role)->where('menu_item_id', $menu_item_id)->get($this->table)->num_rows() > 0;
    }

    /** Ghi đè toàn bộ quyền của 1 vai trò bằng $menu_item_ids (mảng id). */
    public function set_for_role($role, $menu_item_ids)
    {
        $this->db->where('role', $role)->delete($this->table);
        foreach (array_unique(array_map('intval', $menu_item_ids)) as $id)
        {
            $this->db->insert($this->table, array('role' => $role, 'menu_item_id' => $id));
        }
    }
}
