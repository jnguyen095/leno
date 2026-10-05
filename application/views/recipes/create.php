<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3">Thêm công thức</h4>
  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
  <?php echo form_open(current_url()); ?>
    <div class="mb-3">
      <label class="form-label">Tên món</label>
      <input type="text" name="name" class="form-control form-control-lg" placeholder="VD: Kem muối" required autofocus>
      <div class="form-text">Sau khi lưu, bạn sẽ thêm thành phần (nguyên liệu + định lượng) ở bước tiếp theo.</div>
    </div>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg">Tiếp tục</button>
      <a href="<?php echo site_url('me/recipes'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
