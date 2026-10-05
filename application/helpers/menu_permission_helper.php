<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('menu_permission_resolve'))
{
    /**
     * Tìm menu_item khớp với (controller, method) hiện tại. Dùng trong
     * MY_Controller để quyết định: có menu_item khớp thì RBAC động là
     * "thẩm quyền" duy nhất (thay hẳn $allowed_roles cho request này);
     * không khớp thì rơi về $allowed_roles tĩnh như trước giờ (không đổi
     * hành vi với các controller/method chưa được đưa vào danh mục).
     */
    function menu_permission_resolve($controller, $method)
    {
        $CI =& get_instance();
        $CI->load->model('Menu_item_model');

        $controller = strtolower($controller);
        $method = strtolower($method);

        foreach ($CI->Menu_item_model->get_by_controller($controller) as $item)
        {
            if ($item['methods'] === NULL)
            {
                return $item;
            }
            $methods = array_map('trim', explode(',', strtolower($item['methods'])));
            if (in_array($method, $methods, TRUE))
            {
                return $item;
            }
        }
        return NULL;
    }
}

if ( ! function_exists('menu_permission_user_can'))
{
    /** ADMIN luôn TRUE. Ngoài ra: được cấp qua vai trò HOẶC cấp thêm riêng cho user đó. */
    function menu_permission_user_can($current_user, $menu_item_id)
    {
        if ($current_user['role'] === 'ADMIN')
        {
            return TRUE;
        }

        $CI =& get_instance();
        $CI->load->model(array('Role_menu_permission_model', 'User_menu_permission_model'));

        if ($CI->Role_menu_permission_model->role_has($current_user['role'], $menu_item_id))
        {
            return TRUE;
        }
        return $CI->User_menu_permission_model->user_has($current_user['id'], $menu_item_id);
    }
}

if ( ! function_exists('menu_permission_user_can_key'))
{
    /** Tiện dùng trong view (header.php) — kiểm tra theo menu_key thay vì id. Menu_key không tồn tại -> FALSE. */
    function menu_permission_user_can_key($current_user, $menu_key)
    {
        if ($current_user['role'] === 'ADMIN')
        {
            return TRUE;
        }

        $CI =& get_instance();
        $CI->load->model('Menu_item_model');
        $item = $CI->Menu_item_model->get_by_key($menu_key);
        if ( ! $item)
        {
            return FALSE;
        }
        return menu_permission_user_can($current_user, $item['id']);
    }
}
