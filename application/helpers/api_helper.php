<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Chuyển dòng DB (mọi cột đều là chuỗi) thành JSON có kiểu rõ ràng cho API ứng dụng
 * di động /api/v1. Ảnh trả về dạng đường dẫn tương đối ("assets/..."), ứng dụng tự
 * ghép với địa chỉ máy chủ nó đang dùng (base_url của web có thể là localhost).
 */

if ( ! function_exists('api_money'))
{
    function api_money($value)
    {
        return $value === NULL ? 0.0 : (float) $value;
    }
}

if ( ! function_exists('api_image'))
{
    function api_image($image)
    {
        return $image ? 'assets/'.ltrim($image, '/') : NULL;
    }
}

if ( ! function_exists('api_user_can_menu'))
{
    /**
     * Cùng quy tắc với MY_Controller: ADMIN luôn được; màn hình đã đưa vào danh mục
     * "Gán quyền menu" thì theo quyền gán động; chưa có thì theo $fallback_roles.
     */
    function api_user_can_menu($user, $menu_key, array $fallback_roles)
    {
        if ($user['role'] === 'ADMIN')
        {
            return TRUE;
        }

        $CI =& get_instance();
        $CI->load->helper('menu_permission');
        $CI->load->model('Menu_item_model');
        return $CI->Menu_item_model->get_by_key($menu_key)
            ? menu_permission_user_can_key($user, $menu_key)
            : in_array($user['role'], $fallback_roles, TRUE);
    }
}

if ( ! function_exists('api_pos_permissions'))
{
    /** Màn hình POS của ứng dụng: Sơ đồ bàn (web: Tables) và Gọi món/Thanh toán (web: Orders). */
    function api_pos_permissions($user)
    {
        $roles = array('STAFF', 'CASHIER', 'ADMIN');
        return array(
            'tables' => api_user_can_menu($user, 'tables', $roles),
            'orders' => api_user_can_menu($user, 'orders', $roles),
        );
    }
}

if ( ! function_exists('api_user'))
{
    /** $permissions: ['tables' => bool, 'orders' => bool] — màn hình ứng dụng được mở. */
    function api_user($user, array $permissions = array())
    {
        return array(
            'id'          => (int) $user['id'],
            'username'    => $user['username'],
            'fullname'    => $user['fullname'],
            'role'        => $user['role'],
            'role_label'  => role_label($user['role']),
            'permissions' => $permissions,
        );
    }
}

if ( ! function_exists('api_order_summary'))
{
    function api_order_summary($order)
    {
        if ( ! $order) return NULL;
        return array(
            'id'           => (int) $order['id'],
            'order_no'     => $order['order_no'],
            'status'       => $order['status'],
            'total_amount' => api_money($order['total_amount']),
            'item_count'   => isset($order['item_count']) ? (int) $order['item_count'] : NULL,
            'created_at'   => isset($order['created_at']) ? $order['created_at'] : NULL,
        );
    }
}

if ( ! function_exists('api_table'))
{
    function api_table($t)
    {
        return array(
            'id'          => (int) $t['id'],
            'code'        => $t['table_code'],
            'name'        => $t['table_name'],
            'note'        => $t['note'],
            'capacity'    => (int) $t['capacity'],
            'sort_order'  => (int) $t['sort_order'],
            'is_takeaway' => (bool) $t['is_takeaway'],
            'status'      => $t['status'],
            'order'       => isset($t['order']) ? api_order_summary($t['order']) : NULL,
        );
    }
}

if ( ! function_exists('api_product'))
{
    function api_product($p)
    {
        return array(
            'id'          => (int) $p['id'],
            'category_id' => (int) $p['category_id'],
            'sku'         => $p['sku'],
            'name'        => $p['product_name'],
            'price'       => api_money($p['price']),
            'image'       => api_image($p['image']),
            'description' => $p['description'],
        );
    }
}

if ( ! function_exists('api_order'))
{
    function api_order($order)
    {
        return array(
            'id'              => (int) $order['id'],
            'order_no'        => $order['order_no'],
            'order_type'      => $order['order_type'],
            'status'          => $order['status'],
            'note'            => $order['note'],
            'table_id'        => $order['table_id'] !== NULL ? (int) $order['table_id'] : NULL,
            'table_name'      => $order['table_name'] ?: 'Mang đi',
            'table_note'      => $order['table_note'],
            'created_by_name' => $order['created_by_name'],
            'subtotal'        => api_money($order['subtotal']),
            'discount_amount' => api_money($order['discount_amount']),
            'vat_amount'      => api_money($order['vat_amount']),
            'total_amount'    => api_money($order['total_amount']),
            'created_at'      => $order['created_at'],
            'paid_at'         => $order['paid_at'],
        );
    }
}

if ( ! function_exists('api_order_item'))
{
    function api_order_item($it)
    {
        $target = $it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0;
        return array(
            'id'            => (int) $it['id'],
            'product_id'    => (int) $it['product_id'],
            'product_name'  => $it['product_name'],
            'image'         => api_image(isset($it['image']) ? $it['image'] : NULL),
            'qty'           => (int) $it['qty'],
            'notified_qty'  => (int) $it['notified_qty'],
            'price'         => api_money($it['price']),
            'amount'        => api_money($it['amount']),
            'note'          => $it['note'],
            'notified_note' => $it['notified_note'],
            'status'        => $it['status'],
            // Còn phần chưa báo bếp (món mới, đổi số lượng, đổi ghi chú).
            'pending'       => $target !== (int) $it['notified_qty'] || Order_item_model::note_changed($it),
        );
    }
}

if ( ! function_exists('api_payment'))
{
    function api_payment($p)
    {
        if ( ! $p) return NULL;
        return array(
            'id'              => (int) $p['id'],
            'payment_method'  => $p['payment_method'],
            'method_label'    => payment_method_label($p['payment_method']),
            'amount'          => api_money($p['amount']),
            'received_amount' => api_money($p['received_amount']),
            'change_amount'   => api_money($p['change_amount']),
            'paid_at'         => $p['paid_at'],
        );
    }
}
