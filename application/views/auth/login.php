<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Đăng nhập - Leno</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo asset_url('assets/css/style_v1.3.css'); ?>" rel="stylesheet">
</head>
<body class="bg-brand d-flex align-items-center" style="min-height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-11 col-sm-8 col-md-5 col-lg-4">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-sm-5 pt-sm-0">
          <div class="text-center mb-4">
            <img src="<?=base_url('/assets/img/leno-logo.jpg')?>" style="width:150px;margin-top:20px"?>
            <h4 class="mt-2 mb-0 fw-bold"></h4>
            <small class="text-muted"></small>
          </div>

          <?php if ( ! empty($error)): ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
          <?php endif; ?>

          <?php echo form_open('login'); ?>
            <div class="mb-3">
              <label class="form-label">Tên đăng nhập</label>
              <input type="text" name="username" class="form-control form-control-lg" required autofocus value="<?php echo set_value('username'); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Mật khẩu</label>
              <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" name="remember" value="1" class="form-check-input" id="rememberMe" checked>
              <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
            </div>
            <button type="submit" class="btn btn-brand btn-lg w-100">Đăng nhập</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
