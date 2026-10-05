<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('json_response'))
{
    function json_response($data, $status_code = 200)
    {
        $ci =& get_instance();
        $ci->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }
}

if ( ! function_exists('money_format_vnd'))
{
    function money_format_vnd($amount)
    {
        return number_format((float) $amount, 0, ',', '.').'';
    }
}

if ( ! function_exists('order_status_badge'))
{
    function order_status_badge($status)
    {
        $map = array(
            'OPEN'          => 'primary',
            'WAIT_PAYMENT'  => 'warning',
            'PAID'          => 'success',
            'CANCELLED'     => 'secondary',
        );
        return isset($map[$status]) ? $map[$status] : 'light';
    }
}

if ( ! function_exists('table_status_badge'))
{
    function table_status_badge($status)
    {
        $map = array(
            'AVAILABLE'    => 'success',
            'OPEN'         => 'primary',
            'WAIT_PAYMENT' => 'warning',
            'PAID'         => 'info',
        );
        return isset($map[$status]) ? $map[$status] : 'light';
    }
}

if ( ! function_exists('payment_method_label'))
{
    function payment_method_label($method)
    {
        $map = array(
            'CASH'     => 'Tiền mặt',
            'TRANSFER' => 'Chuyển khoản',
            'CARD'     => 'Thẻ',
            'QR'       => 'QR Pay',
        );
        return isset($map[$method]) ? $map[$method] : $method;
    }
}

if ( ! function_exists('role_label'))
{
    function role_label($role)
    {
        $map = array(
            'ADMIN'   => 'Quản trị viên',
            'CASHIER' => 'Thu ngân',
            'BARISTA' => 'Pha chế',
            'STAFF'   => 'Nhân viên phục vụ',
            'STOCKTAKER' => 'Nhân viên kiểm kho',
        );
        return isset($map[$role]) ? $map[$role] : $role;
    }
}

if ( ! function_exists('storage_type_label'))
{
    function storage_type_label($storage_type)
    {
        $map = array(
            'COLD' => '<span class="badge bg-info text-dark"><i class="bi bi-snow"></i> Lạnh</span>',
            'DRY'  => '<span class="badge bg-warning text-dark"><i class="bi bi-sun"></i> Khô</span>',
        );
        return isset($map[$storage_type]) ? $map[$storage_type] : $storage_type;
    }
}

if ( ! function_exists('revenue_category_label'))
{
    function revenue_category_label($category)
    {
        $map = array(
            'KHU_VUI_CHOI' => 'Khu Vui Chơi',
            'NUOC_DO_AN'   => 'Nước & Đồ Ăn',
            'PHOTOBOOTH'   => 'Photobooth',
            'GRABFOOD'     => 'Grabfood',
        );
        return isset($map[$category]) ? $map[$category] : $category;
    }
}

if ( ! function_exists('revenue_category_color'))
{
    /** Màu đồng bộ giữa biểu đồ cột (xu hướng theo tháng) và biểu đồ tròn (tỷ lệ theo danh mục). */
    function revenue_category_color($category)
    {
        $map = array(
            'KHU_VUI_CHOI' => '#f59e0b',
            'NUOC_DO_AN'   => '#10b981',
            'PHOTOBOOTH'   => '#ec4899',
            'GRABFOOD'     => '#8b5cf6',
        );
        return isset($map[$category]) ? $map[$category] : '#6f4e37';
    }
}

if ( ! function_exists('revenue_change_label'))
{
    /**
     * So với tháng trước — mảng ['text' => "Tăng 5%"/"Giảm 20%"/..., 'class' => CSS class màu chữ].
     * $previous = 0 mà $current > 0 thì chưa có gì để tính % (chia cho 0) -> "Mới".
     * Cả 2 = 0 -> không có dữ liệu để so sánh -> "—". Lệch dưới 0.05% coi như "Không đổi".
     */
    function revenue_change_label($current, $previous)
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous <= 0)
        {
            return $current > 0
                ? array('text' => 'Mới', 'class' => 'text-primary')
                : array('text' => '—', 'class' => 'text-muted');
        }

        $change = (($current - $previous) / $previous) * 100;
        $formatted = rtrim(rtrim(number_format(abs($change), 1, '.', ''), '0'), '.');

        if ($change > 0.05)
        {
            return array('text' => 'Tăng '.$formatted.'%', 'class' => 'text-success');
        }
        if ($change < -0.05)
        {
            return array('text' => 'Giảm '.$formatted.'%', 'class' => 'text-danger');
        }
        return array('text' => 'Không đổi', 'class' => 'text-muted');
    }
}

if ( ! function_exists('audit_module_label'))
{
    /** Tên tiếng Việt cho module trong audit_logs — dùng cho màn nhật ký hệ thống. */
    function audit_module_label($module)
    {
        $map = array(
            'auth'                   => 'Đăng nhập/xuất',
            'user'                   => 'Người dùng',
            'category'               => 'Danh mục món',
            'product'                => 'Sản phẩm',
            'table'                  => 'Bàn',
            'order'                  => 'Đơn hàng',
            'order_item'             => 'Món trong đơn',
            'kitchen_ticket'         => 'Ticket bếp',
            'payment'                => 'Thanh toán',
            'inventory_category'     => 'Danh mục kho',
            'inventory_unit'         => 'Đơn vị tính',
            'inventory_item'         => 'Sản phẩm kho',
            'dispense_point'         => 'Điểm xuất kho',
            'stock_transaction'      => 'Nhập/xuất/kiểm kho',
            'payroll_settings'       => 'Cấu hình lương',
            'payroll_record'         => 'Dữ liệu lương',
            'payroll_hours'          => 'Giờ làm/ngày nghỉ',
            'role_menu_permissions'  => 'Quyền menu theo vai trò',
            'user_menu_permissions'  => 'Quyền menu riêng nhân viên',
            'settings'               => 'Cài đặt hệ thống',
        );
        return isset($map[$module]) ? $map[$module] : $module;
    }
}

if ( ! function_exists('vn_to_ascii'))
{
    /** Bỏ dấu tiếng Việt (ánh xạ ký tự thủ công — không phụ thuộc iconv/locale). */
    function vn_to_ascii($str)
    {
        static $map = array(
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
            'đ'=>'d',
        );
        return strtr(mb_strtolower($str, 'UTF-8'), $map);
    }
}

if ( ! function_exists('vn_sku_prefix'))
{
    /** "Pha Chế" -> "PH", "Nhà Bếp" -> "NH" — tiền tố SKU tự sinh từ tên danh mục. */
    function vn_sku_prefix($name, $len = 2)
    {
        $ascii = preg_replace('/[^a-z]/', '', vn_to_ascii($name));
        $prefix = strtoupper(substr($ascii, 0, $len));
        return $prefix !== '' ? $prefix : 'SP';
    }
}

if ( ! function_exists('gen_token'))
{
    function gen_token($length = 32)
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}

if ( ! function_exists('gen_no'))
{
    function gen_no($prefix)
    {
        return $prefix.date('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}

if ( ! defined('REMEMBER_COOKIE_NAME'))
{
    define('REMEMBER_COOKIE_NAME', 'kds_remember');
}

if ( ! function_exists('set_remember_cookie'))
{
    /** $ttl_seconds = NULL giữ nguyên tới đúng $expires_at (không gia hạn thêm). */
    function set_remember_cookie($cookie_value, $ttl_seconds)
    {
        $ci =& get_instance();
        $ci->input->set_cookie(array(
            'name'     => REMEMBER_COOKIE_NAME,
            'value'    => $cookie_value,
            'expire'   => $ttl_seconds,
            'httponly' => TRUE,
            'samesite' => 'Lax',
        ));
    }
}

if ( ! function_exists('clear_remember_cookie'))
{
    function clear_remember_cookie()
    {
        $ci =& get_instance();
        $ci->input->set_cookie(array('name' => REMEMBER_COOKIE_NAME, 'value' => '', 'expire' => -1));
    }
}

if ( ! function_exists('attempt_remember_login'))
{
    /**
     * Tự đăng nhập lại từ cookie "ghi nhớ đăng nhập" khi phiên CI session đã hết
     * hạn (2 giờ) nhưng cookie 1 năm vẫn còn hiệu lực. Dùng cho cả trang login
     * (GET, tránh bắt gõ lại mật khẩu) lẫn MY_Controller (mọi trang cần đăng nhập).
     * Trả về mảng user (đã set vào session) hoặc NULL nếu không có/không hợp lệ.
     */
    function attempt_remember_login()
    {
        $ci =& get_instance();

        $cookie_value = $ci->input->cookie(REMEMBER_COOKIE_NAME);
        if ( ! $cookie_value)
        {
            return NULL;
        }

        $ci->load->model('User_remember_model');
        $result = $ci->User_remember_model->verify_and_rotate($cookie_value);
        if ( ! $result)
        {
            clear_remember_cookie();
            return NULL;
        }

        $ci->load->model('User_model');
        $user = $ci->User_model->get_by_id($result['user_id']);
        if ( ! $user || $user['status'] !== 'ACTIVE')
        {
            clear_remember_cookie();
            return NULL;
        }

        unset($user['password']);
        $ci->session->set_userdata('user', $user);
        set_remember_cookie($result['cookie'], strtotime($result['expires_at']) - time());

        return $user;
    }
}
