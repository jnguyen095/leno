<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?php echo isset($page_title) ? $page_title : 'Leno'; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo asset_url('assets/css/style_v1.3.css'); ?>" rel="stylesheet">
<link href="<?php echo asset_url('assets/css/receipt_k80.css'); ?>" rel="stylesheet">
<link rel="icon" href="<?php echo base_url('assets/img/favicon/favicon.ico'); ?>">
</head>
<body>
<script>
// Trang đang chạy bên trong khung toàn màn hình POS (xem views/orders/_pos_tabs.php) ->
// ẩn thanh menu ngay đầu <body> để không nháy lên rồi mới ẩn.
try { if (window.self !== window.top && window.parent.LenoPosShell) document.body.classList.add('pos-focus'); } catch (e) {}
</script>
<?php if ( ! empty($current_user)): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-brand sticky-top">
  <div class="container-fluid">
    <?php
      $brand_home = 'dashboard';
      if ($current_user['role'] === 'STOCKTAKER') $brand_home = 'stock/adjust';
    ?>
    <a class="navbar-brand" href="<?php echo site_url('me/'.$brand_home); ?>">
      <img src="<?=base_url("/assets/img/nang-nghieng-icon-white.png")?>" width="130px"/>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <?php
        // $this trong view là CI_Loader, không phải controller — phải lấy
        // singleton thật qua get_instance() để load helper/model tại đây.
        $CI =& get_instance();
        $CI->load->helper('menu_permission');
        $can = function($key) use ($current_user, $CI) { return menu_permission_user_can_key($current_user, $key); };

        $can_stock_in = $can('inventory.stock_in');
        $can_stock_out = $can('inventory.stock_out');
        $can_stock_adjust = $can('inventory.stock_adjust');
        $can_inventory_items = $can('inventory.items');
        $can_inventory_history = $can('inventory.history');
        $show_inventory_menu = $can_stock_in || $can_stock_out || $can_stock_adjust || $can_inventory_items || $can_inventory_history;

        // Quản trị: trang /me/admin + menu trái các trang quản trị (xem admin_menu_helper).
        $CI->load->helper('admin_menu');
        $admin_groups = admin_menu_groups($current_user);
        $show_admin_menu = (bool) $admin_groups;
        $admin_active_url = $show_admin_menu ? admin_menu_active_url($admin_groups, $CI->uri->uri_string()) : NULL;
      ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($can('dashboard')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo site_url('me/dashboard'); ?>"><i class="bi bi-speedometer2"></i> Tổng quan</a></li>
        <?php endif; ?>
        <?php if ($can('tables')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo site_url('me/tables'); ?>"><i class="bi bi-grid-3x3-gap"></i> Bàn</a></li>
        <?php endif; ?>
        <?php if ($can('orders')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo site_url('me/orders'); ?>"><i class="bi bi-receipt"></i> Đơn hàng</a></li>
        <?php endif; ?>
        <?php if ($can('cashbook')): // Sổ quỹ: ADMIN hoặc vai trò được gán menu "Sổ quỹ". ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo site_url('me/cashbook'); ?>"><i class="bi bi-journal-bookmark"></i> Sổ quỹ</a></li>
        <?php endif; ?>
        <?php if ($can('pha_che')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo site_url('me/pha-che'); ?>"><i class="bi bi-cup-straw"></i> Pha chế</a></li>
        <?php endif; ?>
        <?php if ($show_inventory_menu):
          $CI->load->model('Inventory_item_model');
          $low_stock_count = $CI->Inventory_item_model->count_low_stock();
        ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-boxes"></i> Kho hàng
            <?php if ($low_stock_count > 0): ?><span class="badge bg-danger rounded-pill"><?php echo $low_stock_count; ?></span><?php endif; ?>
          </a>
          <ul class="dropdown-menu">
            <?php if ($can_stock_in): ?><li><a class="dropdown-item" href="<?php echo site_url('me/stock/in'); ?>"><i class="bi bi-box-arrow-in-down text-success"></i> Nhập kho</a></li><?php endif; ?>
            <?php if ($can_stock_out): ?><li><a class="dropdown-item" href="<?php echo site_url('me/stock/out'); ?>"><i class="bi bi-box-arrow-up text-danger"></i> Xuất kho</a></li><?php endif; ?>
            <?php if ($can_stock_adjust): ?><li><a class="dropdown-item" href="<?php echo site_url('me/stock/adjust'); ?>"><i class="bi bi-clipboard-check text-primary"></i> Kiểm kho</a></li><?php endif; ?>
            <?php if ($can_inventory_items): ?><li><a class="dropdown-item" href="<?php echo site_url('me/inventory/items'); ?>">Hàng trong kho<?php if ($low_stock_count > 0): ?> <span class="badge bg-danger rounded-pill"><?php echo $low_stock_count; ?></span><?php endif; ?></a></li><?php endif; ?>
            <?php if ($can_inventory_history): ?><li><a class="dropdown-item" href="<?php echo site_url('me/stock/history'); ?>">Lịch sử nhập/xuất</a></li><?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>
        <?php if ($show_admin_menu): ?>
        <li class="nav-item"><a class="nav-link <?php echo $admin_active_url ? 'active' : ''; ?>" href="<?php echo site_url('me/admin'); ?>"><i class="bi bi-gear"></i> Quản trị</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php // Mở trang web công khai (trang chủ cửa hàng) ở tab mới. ?>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo base_url(); ?>" target="_blank" rel="noopener"
             data-bs-toggle="tooltip" data-bs-placement="bottom" title="Xem trang web" aria-label="Xem trang web">
            <i class="bi bi-globe2"></i><span class="d-lg-none ms-1">Xem trang web</span>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user['fullname']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?php echo site_url('me/payroll'); ?>"><i class="bi bi-cash-coin"></i> Chấm công</a></li>
            <li><a class="dropdown-item" href="<?php echo site_url('me/payroll/bank-info'); ?>"><i class="bi bi-bank"></i> Thông tin ngân hàng</a></li>
            <li><a class="dropdown-item" href="<?php echo site_url('me/change-password'); ?>"><i class="bi bi-key"></i> Đổi mật khẩu</a></li>
            <li><a class="dropdown-item" href="<?php echo site_url('me/logout'); ?>"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<main>
<?php if ( ! empty($admin_active_url)): $CI->admin_shell_open = TRUE; ?>
<?php // Trang quản trị: menu trái (máy tính) / dãy nút trượt ngang (điện thoại); footer đóng khung. ?>
<div class="admin-shell">
  <?php $this->load->view('layout/_admin_nav', array('admin_groups' => $admin_groups, 'admin_active_url' => $admin_active_url)); ?>
  <div class="admin-shell-main">
<?php endif; ?>
