<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Không có quyền truy cập</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div class="text-center">
  <h1 class="display-4 fw-bold text-danger">403</h1>
  <p class="lead">Tài khoản của bạn không có quyền truy cập trang này.</p>
  <?php
  $home = 'dashboard';
  if ( ! empty($current_user['role']))
  {
      switch ($current_user['role'])
      {
          case 'BARISTA': $home = 'pha-che'; break;
          case 'CASHIER': $home = 'tables'; break;
          case 'STOCKTAKER': $home = 'stock/adjust'; break;
      }
  }
  ?>
  <a href="<?php echo site_url('me/'.$home); ?>" class="btn btn-primary">Về trang chủ</a>
</div>
</body>
</html>
