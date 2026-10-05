<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-key"></i> Đổi mật khẩu</h4>

  <?php if ($success): ?>
    <div class="alert alert-success py-2 small">Đổi mật khẩu thành công. Nếu có bật "Ghi nhớ đăng nhập" trên thiết bị khác, bạn sẽ cần đăng nhập lại ở đó.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <?php echo form_open(current_url()); ?>
        <div class="mb-3">
          <label class="form-label">Mật khẩu hiện tại</label>
          <input type="password" name="current_password" class="form-control form-control-lg" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Mật khẩu mới</label>
          <input type="password" name="new_password" class="form-control form-control-lg" required minlength="6">
          <div class="form-text">Ít nhất 6 ký tự.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Xác nhận mật khẩu mới</label>
          <input type="password" name="confirm_password" class="form-control form-control-lg" required minlength="6">
        </div>
        <button class="btn btn-brand btn-lg w-100">Đổi mật khẩu</button>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
