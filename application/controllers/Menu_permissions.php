<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Màn ADMIN cấu hình RBAC động: gán menu cho từng vai trò, và cấp thêm menu
 * riêng cho 1 nhân viên cụ thể (cộng thêm vào quyền theo vai trò, không
 * thay thế). Cố tình KHÔNG được liệt kê trong menu_items — luôn dùng
 * $allowed_roles tĩnh (ADMIN) để không bao giờ tự khóa được màn cấu hình
 * quyền chính nó.
 */
class Menu_permissions extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Menu_item_model', 'Role_menu_permission_model', 'User_menu_permission_model', 'User_model'));
    }

    /** Gán menu theo vai trò. */
    public function index()
    {
        $role = $this->input->get('role') ?: 'STAFF';
        $roles = array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER');

        if ($this->input->method() === 'post')
        {
            $role = $this->input->post('role');
            if (in_array($role, $roles, TRUE) && $role !== 'ADMIN')
            {
                $ids = (array) $this->input->post('menu_item_ids');
                $this->Role_menu_permission_model->set_for_role($role, $ids);
                $this->audit('role_menu_permissions', 'UPDATE', NULL, array('role' => $role, 'menu_item_ids' => $ids));
                $this->session->set_flashdata('success', 'Đã lưu menu cho vai trò '.role_label($role).'.');
            }
            redirect('me/menu-permissions?role='.$role);
            return;
        }

        $menu_items = $this->Menu_item_model->get_all();
        $granted = $role === 'ADMIN' ? NULL : $this->Role_menu_permission_model->get_menu_item_ids_for_role($role);

        $data = array(
            'page_title'   => 'Gán menu theo vai trò',
            'current_user' => $this->current_user,
            'roles'        => $roles,
            'role'         => $role,
            'menu_items'   => $menu_items,
            'granted'      => $granted,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('menu_permissions/index', $data);
        $this->load->view('layout/footer');
    }

    /** Cấp thêm menu riêng cho 1 nhân viên, ngoài quyền mặc định theo vai trò. */
    public function user($user_id = NULL)
    {
        if ($this->input->method() === 'post')
        {
            $user_id = (int) $this->input->post('user_id');
            $user = $this->User_model->get_by_id($user_id);
            if ($user && $user['role'] !== 'ADMIN')
            {
                $ids = (array) $this->input->post('menu_item_ids');
                $this->User_menu_permission_model->set_for_user($user_id, $ids);
                $this->audit('user_menu_permissions', 'UPDATE', NULL, array('user_id' => $user_id, 'menu_item_ids' => $ids));
                $this->session->set_flashdata('success', 'Đã lưu menu cấp thêm cho '.$user['fullname'].'.');
            }
            redirect('me/menu-permissions/user/'.$user_id);
            return;
        }

        $users = $this->User_model->get_all(NULL, 'ACTIVE', NULL);
        $users = array_values(array_filter($users, function($u) { return $u['role'] !== 'ADMIN'; }));

        $target_user = NULL;
        $menu_items = array();
        $role_granted = array();
        $user_granted = array();

        if ($user_id)
        {
            $target_user = $this->User_model->get_by_id($user_id);
            if ($target_user && $target_user['role'] !== 'ADMIN')
            {
                $menu_items = $this->Menu_item_model->get_all();
                $role_granted = $this->Role_menu_permission_model->get_menu_item_ids_for_role($target_user['role']);
                $user_granted = $this->User_menu_permission_model->get_menu_item_ids_for_user($user_id);
            }
            else
            {
                $target_user = NULL;
            }
        }

        $data = array(
            'page_title'    => 'Cấp thêm menu cho nhân viên',
            'current_user'  => $this->current_user,
            'users'         => $users,
            'target_user'   => $target_user,
            'menu_items'    => $menu_items,
            'role_granted'  => $role_granted,
            'user_granted'  => $user_granted,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('menu_permissions/user', $data);
        $this->load->view('layout/footer');
    }
}
