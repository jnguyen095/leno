<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'public/public_site';
$route['404_override'] = 'errors/page_missing';
$route['translate_uri_dashes'] = FALSE;

// Website public (khách hàng, không đăng nhập) — controller/view/asset đều tách
// riêng thư mục, xem application/controllers/Public/Public_site.php.
// "/" giờ là trang chủ public thay vì dashboard nội bộ; nhân viên vào thẳng /login hoặc /me/dashboard.html.
// Chỉ còn 1 trang duy nhất (one-page landing) — Khu vui chơi/Cà phê/
// Photobooth/Khuyến mãi/Liên hệ đều là section trong trang chủ (anchor #kids,
// #cafe...), không còn route riêng.

// Trang nội bộ (cần đăng nhập) đều nằm dưới tiền tố /me/ — vd. /me/dashboard.html
// (đuôi .html do $config['url_suffix']). Trang public (/, /login, /menu/*, /trung-thu,
// /api/*) giữ nguyên không tiền tố. MY_Controller tự redirect URL cũ (thiếu /me/) sang URL mới.
$route['me'] = 'dashboard/index';

// Auth
$route['login'] = 'auth/login';
$route['me/logout'] = 'auth/logout';
$route['me/change-password'] = 'profile/change_password';

// Lương — mọi role xem lương của mình; các route /payroll/admin/* chỉ ADMIN
$route['me/payroll'] = 'payroll/index';
$route['me/payroll/detail'] = 'payroll/detail';
$route['me/payroll/bank-info'] = 'payroll/bank_info';
$route['me/payroll/admin'] = 'payroll/admin';
$route['me/payroll/settings/(:num)'] = 'payroll/settings/$1';
$route['me/payroll/record/(:num)'] = 'payroll/record/$1';
$route['me/payroll/record/(:num)/bonus/add'] = 'payroll/add_bonus/$1';
$route['me/payroll/record/(:num)/bonus/(:num)/delete'] = 'payroll/delete_bonus/$1/$2';
$route['me/payroll/hours/(:num)'] = 'payroll/hours/$1';

// Staff / Cashier / Barista / Admin screens
$route['me/dashboard'] = 'dashboard/index';
$route['me/tables'] = 'tables/index';
$route['me/tables/manage'] = 'tables/manage';
$route['me/tables/manage/create'] = 'tables/manage_create';
$route['me/tables/manage/(:num)/edit'] = 'tables/manage_edit/$1';
$route['me/tables/manage/(:num)/delete'] = 'tables/manage_delete/$1';
$route['me/tables/manage/(:num)/reset-status'] = 'tables/manage_reset_status/$1';

$route['me/tables/(:num)'] = 'tables/detail/$1';
$route['me/tables/(:num)/open'] = 'tables/open/$1';
$route['me/tables/(:num)/transfer'] = 'tables/transfer/$1';
$route['me/tables/(:num)/merge'] = 'tables/merge/$1';
$route['me/tables/(:num)/print-provisional'] = 'tables/print_provisional/$1';
$route['me/tables/(:num)/qr'] = 'tables/qr/$1';

$route['me/orders'] = 'orders/index';
$route['me/orders/(:num)'] = 'orders/detail/$1';
$route['me/orders/(:num)/add-item'] = 'orders/add_item/$1';
$route['me/orders/(:num)/ticket-status'] = 'orders/ticket_status/$1';
$route['me/orders/(:num)/update-item/(:num)'] = 'orders/update_item/$1/$2';
$route['me/orders/(:num)/cancel-item/(:num)'] = 'orders/cancel_item/$1/$2';
$route['me/orders/(:num)/checkout'] = 'orders/checkout/$1';

$route['me/takeaway/create'] = 'takeaway/create';

$route['me/kitchen'] = 'kitchen/index';
$route['me/kitchen/ticket/(:num)'] = 'kitchen/ticket/$1';
$route['me/kitchen/ticket/(:num)/status'] = 'kitchen/update_status/$1';

$route['me/cashier'] = 'cashier/index';
$route['me/cashier/(:num)'] = 'cashier/detail/$1';
$route['me/cashier/(:num)/close-bill'] = 'cashier/close_bill/$1';
$route['me/cashier/(:num)/pay'] = 'cashier/pay/$1';
$route['me/cashier/(:num)/invoice'] = 'cashier/invoice/$1';

$route['me/payments'] = 'payments/index';

// Báo cáo doanh thu — nhập tay theo tháng/danh mục (POS bán hàng dùng hệ thống khác, không tự tính từ orders trong app này nữa)
$route['me/reports'] = 'revenue_report/index';
$route['me/reports/entry'] = 'revenue_report/entry';

$route['me/users'] = 'users/index';
$route['me/users/create'] = 'users/create';
$route['me/users/(:num)/edit'] = 'users/edit/$1';
$route['me/users/(:num)/delete'] = 'users/delete/$1';

$route['me/products'] = 'products/index';
$route['me/products/create'] = 'products/create';
$route['me/products/(:num)/edit'] = 'products/edit/$1';
$route['me/products/(:num)/delete'] = 'products/delete/$1';
$route['me/products/import'] = 'products/import';
$route['me/products/import-template'] = 'products/import_template';

$route['me/categories'] = 'categories/index';
$route['me/categories/create'] = 'categories/create';
$route['me/categories/(:num)/edit'] = 'categories/edit/$1';
$route['me/categories/(:num)/delete'] = 'categories/delete/$1';

// Pha chế — xem công thức không kèm cost, cho nhân viên bar/bếp (khác /recipes của ADMIN)
$route['me/pha-che'] = 'pha_che/index';
$route['me/pha-che/(:num)'] = 'pha_che/view/$1';

$route['me/recipes'] = 'recipes/index';
$route['me/recipes/create'] = 'recipes/create';
$route['me/recipes/(:num)/edit'] = 'recipes/edit/$1';
$route['me/recipes/(:num)/delete'] = 'recipes/delete/$1';
$route['me/recipes/(:num)/ingredients/add'] = 'recipes/add_ingredient/$1';
$route['me/recipes/(:num)/ingredients/(:num)/delete'] = 'recipes/delete_ingredient/$1/$2';

$route['me/settings'] = 'settings/index';
$route['me/audit-logs'] = 'audit_logs/index';

// RBAC động — gán menu theo vai trò / cấp thêm cho riêng 1 nhân viên
$route['me/menu-permissions'] = 'menu_permissions/index';
$route['me/menu-permissions/user'] = 'menu_permissions/user';
$route['me/menu-permissions/user/(:num)'] = 'menu_permissions/user/$1';

// Kho hàng (Inventory / Stock)
$route['me/inventory/categories'] = 'inventory_categories/index';
$route['me/inventory/categories/create'] = 'inventory_categories/create';
$route['me/inventory/categories/(:num)/edit'] = 'inventory_categories/edit/$1';
$route['me/inventory/categories/(:num)/delete'] = 'inventory_categories/delete/$1';

$route['me/inventory/dispense-points'] = 'dispense_points/index';
$route['me/inventory/dispense-points/create'] = 'dispense_points/create';
$route['me/inventory/dispense-points/(:num)/edit'] = 'dispense_points/edit/$1';
$route['me/inventory/dispense-points/(:num)/delete'] = 'dispense_points/delete/$1';

$route['me/inventory/units'] = 'inventory_units/index';
$route['me/inventory/units/create'] = 'inventory_units/create';
$route['me/inventory/units/(:num)/edit'] = 'inventory_units/edit/$1';
$route['me/inventory/units/(:num)/delete'] = 'inventory_units/delete/$1';

$route['me/inventory/items'] = 'inventory_items/index';
$route['me/inventory/items/create'] = 'inventory_items/create';
$route['me/inventory/items/(:num)/edit'] = 'inventory_items/edit/$1';
$route['me/inventory/items/(:num)/delete'] = 'inventory_items/delete/$1';
$route['me/inventory/items/import'] = 'inventory_items/import';
$route['me/inventory/items/import-template'] = 'inventory_items/import_template';
$route['me/inventory/items/export'] = 'inventory_items/export';
$route['me/inventory/items/print'] = 'inventory_items/print_list';
$route['me/inventory/items/search'] = 'inventory_items/search';
$route['me/inventory/items/by-category'] = 'inventory_items/by_category';
$route['me/inventory/items/next-sku'] = 'inventory_items/next_sku';

$route['me/stock/in'] = 'stock/in';
$route['me/stock/in/import'] = 'stock/in_import';
$route['me/stock/in/import-template'] = 'stock/in_import_template';
$route['me/stock/out'] = 'stock/out';
$route['me/stock/adjust'] = 'stock/adjust';
$route['me/stock/history'] = 'stock/history';

// Customer QR Ordering (public, no auth)
$route['menu/(:any)'] = 'public/menu/index/$1';
$route['menu/(:any)/cart'] = 'public/menu/cart/$1';
$route['menu/(:any)/history'] = 'public/menu/history/$1';
$route['menu/(:any)/(:any)'] = 'public/menu/visit/$1/$2';

// Trung Thu gift registration (public, no auth) — trang quản trị (trung-thu/admin/*)
// vẫn là controller nội bộ Trung_thu_admin, chỉ trang đăng ký mới nằm trong Public/.
$route['trung-thu'] = 'public/trung_thu/index';
$route['trung-thu/thank-you'] = 'public/trung_thu/thank_you';
$route['trung-thu/thank-you/(:any)'] = 'public/trung_thu/thank_you/$1';
$route['me/trung-thu/admin'] = 'trung_thu_admin/index';
$route['me/trung-thu/admin/export'] = 'trung_thu_admin/export';
$route['me/trung-thu/admin/(:num)/edit'] = 'trung_thu_admin/edit/$1';
$route['me/trung-thu/admin/(:num)/delete'] = 'trung_thu_admin/delete/$1';

// JSON API — per SDS section 12 (customer-facing, token based)
$route['api/order/create'] = 'api_order/create';
$route['api/order/add-item'] = 'api_order/add_item';
$route['api/order/remove-item'] = 'api_order/remove_item';
$route['api/order/current-by-token/(:any)/(:any)'] = 'api_order/current_by_token/$1/$2';
$route['api/payment'] = 'api_payment/create';
$route['api/call/create'] = 'api_call/create';

// JSON API — internal polling for staff/KDS screens
$route['api/kitchen/tickets'] = 'api_kitchen/tickets';
$route['api/kitchen/ticket/(:num)/status'] = 'api_kitchen/update_status/$1';
$route['api/kitchen/ticket-item/(:num)/status'] = 'api_kitchen/update_item_status/$1';
$route['api/tables/status'] = 'api_tables/status';
$route['api/assistance/pending'] = 'api_assistance/pending';
$route['api/assistance/(:num)/resolve'] = 'api_assistance/resolve/$1';

// Telegram bot webhook (public, secret-token based — see application/config/telegram.php)
$route['telegram/webhook'] = 'telegram_webhook/handle';

// Các trang nội bộ khác chưa khai báo route riêng: /me/<controller>/<method>/...
// Phải đặt CUỐI file — đứng trước thì nuốt mất các route me/... khai báo sau nó.
$route['me/(.+)'] = '$1';
