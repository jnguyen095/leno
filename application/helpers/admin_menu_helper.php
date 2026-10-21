<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Menu Quản trị dùng chung: trang /me/admin (lưới chức năng), menu trái của các trang quản trị
 * (điện thoại: dãy nút trượt ngang) và link "Quản trị" trên thanh điều hướng.
 */
if ( ! function_exists('admin_menu_groups'))
{
    /**
     * Các nhóm chức năng người dùng được phép: [['title', 'icon', [['url', 'icon', 'label', 'desc'], ...]], ...].
     * Nhóm không còn mục nào thì bỏ.
     */
    function admin_menu_groups($user)
    {
        $CI =& get_instance();
        $CI->load->helper('menu_permission');
        $can = function ($key) use ($user) { return menu_permission_user_can_key($user, $key); };
        $is_admin = $user['role'] === 'ADMIN';

        $groups = array(
            array('Bán hàng', 'bi-shop', array(
                array($is_admin, 'me/tables/manage', 'bi-grid-3x3-gap', 'Quản lý bàn', 'Thêm, sửa, sắp xếp bàn'),
                array($can('admin.categories'), 'me/categories', 'bi-collection', 'Danh mục', 'Nhóm món trên thực đơn'),
                array($can('admin.products'), 'me/products', 'bi-cup-hot', 'Món', 'Món bán, giá, hình ảnh'),
                array($is_admin, 'me/recipes', 'bi-egg-fried', 'Công thức pha chế', 'Nguyên liệu từng món'),
            )),
            array('Giá & Khuyến mãi', 'bi-tags', array(
                array($is_admin, 'me/price-books', 'bi-tags', 'Bảng giá', 'Giá riêng theo dịp'),
                array($is_admin, 'me/discounts', 'bi-percent', 'Khuyến mãi', 'Giảm trên tổng đơn'),
            )),
            // Sổ quỹ có link riêng trên thanh điều hướng (gán quyền được cho vai trò khác) nên không đặt ở đây,
            // tránh hiện "Quản trị" cho người chỉ có quyền Sổ quỹ.
            array('Tài chính', 'bi-journal-bookmark', array(
                array($is_admin, 'me/cashbook/categories', 'bi-tags', 'Loại thu chi', 'Danh mục thu / chi của Sổ quỹ'),
            )),
            array('Kho', 'bi-box-seam', array(
                array($can('admin.inventory_categories'), 'me/inventory/categories', 'bi-box-seam', 'Danh mục kho', 'Nhóm nguyên liệu'),
                array($can('admin.inventory_units'), 'me/inventory/units', 'bi-rulers', 'Đơn vị tính', 'Gram, ml, hộp...'),
                array($can('admin.dispense_points'), 'me/inventory/dispense-points', 'bi-signpost-split', 'Điểm xuất kho', 'Nơi nhận hàng xuất'),
            )),
            array('Nhân sự', 'bi-people', array(
                array($can('admin.users'), 'me/users', 'bi-person-badge', 'Người dùng', 'Tài khoản, vai trò'),
                array($is_admin, 'me/menu-permissions', 'bi-shield-lock', 'Gán quyền menu', 'Quyền theo vai trò'),
                array($can('admin.payroll'), 'me/payroll/admin', 'bi-wallet2', 'Quản lý lương', 'Chấm công, bảng lương'),
            )),
            array('Website', 'bi-globe2', array(
                array($is_admin, 'me/banners', 'bi-collection-play', 'Banner trang chủ', 'Ảnh lớn tự chạy đầu trang chủ'),
                array($is_admin, 'me/news', 'bi-newspaper', 'Tin tức', 'Tin mới, cập nhật trên trang chủ'),
                array($is_admin, 'me/pages', 'bi-file-earmark-richtext', 'Trang nội dung', 'Giới thiệu, khuyến mãi... trên website'),
                array($is_admin, 'me/pages/images', 'bi-images', 'Thư viện ảnh', 'Ảnh dùng trong trang nội dung'),
            )),
            array('Hệ thống', 'bi-sliders', array(
                array($can('admin.customer_display'), 'me/customer-display', 'bi-display', 'Màn hình khách', 'Màn hình phụ, trình chiếu'),
                array($can('admin.audit_logs'), 'me/audit-logs', 'bi-journal-text', 'Nhật ký hệ thống', 'Ai làm gì, lúc nào'),
                array($can('admin.settings'), 'me/settings', 'bi-gear', 'Cài đặt', 'VAT, in phiếu, chuyển khoản'),
            )),
        );

        $out = array();
        foreach ($groups as $g)
        {
            $items = array();
            foreach ($g[2] as $it)
            {
                if ($it[0]) $items[] = array_slice($it, 1);
            }
            if ($items) $out[] = array($g[0], $g[1], $items);
        }
        return $out;
    }
}

if ( ! function_exists('admin_menu_active_url'))
{
    /** URL mục menu ứng với trang đang mở (dài nhất khớp), NULL nếu không phải trang quản trị. 'me/admin' = trang chính. */
    function admin_menu_active_url(array $groups, $uri)
    {
        if ($uri === 'me/admin') return 'me/admin';
        $best = NULL;
        foreach ($groups as $g)
        {
            foreach ($g[2] as $it)
            {
                if (($uri === $it[0] || strpos($uri, $it[0].'/') === 0) && ($best === NULL || strlen($it[0]) > strlen($best))) $best = $it[0];
            }
        }
        return $best;
    }
}
