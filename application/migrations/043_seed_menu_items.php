<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Seed danh mục menu + quyền theo vai trò — PHẢI khớp chính xác hành vi
 * $allowed_roles/in_array(...) hiện có trong header.php trước khi có RBAC
 * động, để bật tính năng này không âm thầm đổi ai thấy được gì.
 *
 * Các action đã có sẵn 1 lớp chặn riêng bên trong controller (vd
 * Tables::manage*, Inventory_items::create/edit/delete/import đều tự gọi
 * _require_admin()) CỐ TÌNH không liệt kê tách riêng ở đây — chúng vẫn nằm
 * trong menu_key rộng hơn (vd 'tables', 'inventory.items') nhưng lớp chặn
 * inline đó vẫn luôn chạy độc lập, nên gán quyền rộng cho vai trò khác
 * cũng không mở được các action admin-only này.
 */
class Migration_Seed_menu_items extends CI_Migration
{
    public function up()
    {
        $items = array(
            array('group_label' => NULL, 'menu_key' => 'dashboard', 'label' => 'Tổng quan', 'controller' => 'dashboard', 'methods' => NULL, 'route' => 'dashboard', 'sort_order' => 10, 'roles' => array('STAFF', 'CASHIER', 'ADMIN')),
            array('group_label' => NULL, 'menu_key' => 'tables', 'label' => 'Bàn', 'controller' => 'tables', 'methods' => NULL, 'route' => 'tables', 'sort_order' => 20, 'roles' => array('STAFF', 'CASHIER', 'ADMIN')),
            array('group_label' => NULL, 'menu_key' => 'orders', 'label' => 'Đơn hàng', 'controller' => 'orders', 'methods' => NULL, 'route' => 'orders', 'sort_order' => 30, 'roles' => array('STAFF', 'CASHIER', 'ADMIN')),
            array('group_label' => NULL, 'menu_key' => 'takeaway', 'label' => 'Bán mang đi', 'controller' => 'takeaway', 'methods' => NULL, 'route' => 'takeaway/create', 'sort_order' => 40, 'roles' => array('STAFF', 'CASHIER', 'ADMIN')),
            array('group_label' => NULL, 'menu_key' => 'bookings', 'label' => 'Lịch sân', 'controller' => 'bookings', 'methods' => NULL, 'route' => 'bookings', 'sort_order' => 50, 'roles' => array('STAFF', 'CASHIER', 'ADMIN', 'BOOKING')),
            array('group_label' => NULL, 'menu_key' => 'kitchen', 'label' => 'Bếp (KDS)', 'controller' => 'kitchen', 'methods' => NULL, 'route' => 'kitchen', 'sort_order' => 60, 'roles' => array('BARISTA', 'ADMIN')),
            array('group_label' => NULL, 'menu_key' => 'cashier', 'label' => 'Thu ngân', 'controller' => 'cashier', 'methods' => NULL, 'route' => 'cashier', 'sort_order' => 70, 'roles' => array('CASHIER', 'ADMIN')),
            array('group_label' => NULL, 'menu_key' => 'payments', 'label' => 'LS Thanh toán', 'controller' => 'payments', 'methods' => NULL, 'route' => 'payments', 'sort_order' => 80, 'roles' => array('CASHIER', 'ADMIN')),

            array('group_label' => 'Kho hàng', 'menu_key' => 'inventory.stock_in', 'label' => 'Nhập kho', 'controller' => 'stock', 'methods' => 'in,in_import,in_import_template', 'route' => 'stock/in', 'sort_order' => 100, 'roles' => array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER')),
            array('group_label' => 'Kho hàng', 'menu_key' => 'inventory.stock_out', 'label' => 'Xuất kho', 'controller' => 'stock', 'methods' => 'out', 'route' => 'stock/out', 'sort_order' => 110, 'roles' => array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER')),
            array('group_label' => 'Kho hàng', 'menu_key' => 'inventory.stock_adjust', 'label' => 'Kiểm kho', 'controller' => 'stock', 'methods' => 'adjust', 'route' => 'stock/adjust', 'sort_order' => 120, 'roles' => array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER')),
            // Không liệt kê search/by_category/next_sku ở đây: đó là API dùng
            // chung cho cả 3 màn Nhập/Xuất/Kiểm kho, gán quyền hẹp riêng cho
            // "Hàng trong kho" sẽ vô tình làm hỏng màn Nhập/Xuất/Kiểm kho của
            // người chỉ được cấp riêng lẻ 1 trong 3 mục đó. Chúng vẫn được
            // Inventory_items::$allowed_roles tĩnh bảo vệ như cũ.
            array('group_label' => 'Kho hàng', 'menu_key' => 'inventory.items', 'label' => 'Hàng trong kho', 'controller' => 'inventory_items', 'methods' => 'index,create,edit,delete,import,import_template,export,print_list', 'route' => 'inventory/items', 'sort_order' => 130, 'roles' => array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER')),
            array('group_label' => 'Kho hàng', 'menu_key' => 'inventory.history', 'label' => 'Lịch sử nhập/xuất', 'controller' => 'stock', 'methods' => 'history', 'route' => 'stock/history', 'sort_order' => 140, 'roles' => array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER')),

            array('group_label' => 'Quản trị', 'menu_key' => 'admin.categories', 'label' => 'Danh mục', 'controller' => 'categories', 'methods' => NULL, 'route' => 'categories', 'sort_order' => 200, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.products', 'label' => 'Sản phẩm', 'controller' => 'products', 'methods' => NULL, 'route' => 'products', 'sort_order' => 210, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.inventory_categories', 'label' => 'Danh mục kho', 'controller' => 'inventory_categories', 'methods' => NULL, 'route' => 'inventory/categories', 'sort_order' => 220, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.inventory_units', 'label' => 'Đơn vị tính', 'controller' => 'inventory_units', 'methods' => NULL, 'route' => 'inventory/units', 'sort_order' => 230, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.dispense_points', 'label' => 'Điểm xuất kho', 'controller' => 'dispense_points', 'methods' => NULL, 'route' => 'inventory/dispense-points', 'sort_order' => 240, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.users', 'label' => 'Người dùng', 'controller' => 'users', 'methods' => NULL, 'route' => 'users', 'sort_order' => 250, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.payroll', 'label' => 'Quản lý lương', 'controller' => 'payroll', 'methods' => 'admin,settings,record,hours', 'route' => 'payroll/admin', 'sort_order' => 260, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.reports', 'label' => 'Báo cáo', 'controller' => 'reports', 'methods' => NULL, 'route' => 'reports', 'sort_order' => 270, 'roles' => array('ADMIN')),
            array('group_label' => 'Quản trị', 'menu_key' => 'admin.settings', 'label' => 'Cài đặt', 'controller' => 'settings', 'methods' => NULL, 'route' => 'settings', 'sort_order' => 280, 'roles' => array('ADMIN')),
        );

        foreach ($items as $it)
        {
            $roles = $it['roles'];
            unset($it['roles']);
            $this->db->insert('menu_items', $it);
            $menu_item_id = $this->db->insert_id();

            foreach ($roles as $role)
            {
                $this->db->insert('role_menu_permissions', array('role' => $role, 'menu_item_id' => $menu_item_id));
            }
        }
    }

    public function down()
    {
        $this->db->empty_table('role_menu_permissions');
        $this->db->empty_table('user_menu_permissions');
        $this->db->empty_table('menu_items');
    }
}
