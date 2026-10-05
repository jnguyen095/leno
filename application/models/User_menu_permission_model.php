<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Quyền menu cấp thêm riêng cho 1 nhân viên, cộng thêm vào quyền theo vai trò. */
class User_menu_permission_model extends CI_Model
{
    protected $table = 'user_menu_permissions';

    /** Mảng [menu_item_id => TRUE] các mục được cấp thêm riêng cho 1 user. */
    public function get_menu_item_ids_for_user($user_id)
    {
        $rows = $this->db->select('menu_item_id')->where('user_id', $user_id)->get($this->table)->result_array();
        $ids = array();
        foreach ($rows as $r)
        {
            $ids[(int) $r['menu_item_id']] = TRUE;
        }
        return $ids;
    }

    public function user_has($user_id, $menu_item_id)
    {
        return $this->db->where('user_id', $user_id)->where('menu_item_id', $menu_item_id)->get($this->table)->num_rows() > 0;
    }

    /** Ghi đè toàn bộ quyền cấp thêm của 1 user bằng $menu_item_ids (mảng id). */
    public function set_for_user($user_id, $menu_item_ids)
    {
        $this->db->where('user_id', $user_id)->delete($this->table);
        foreach (array_unique(array_map('intval', $menu_item_ids)) as $id)
        {
            $this->db->insert($this->table, array('user_id' => $user_id, 'menu_item_id' => $id));
        }
    }
}
