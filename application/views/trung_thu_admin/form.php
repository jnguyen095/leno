<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><?php echo $page_title; ?></h4>
  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
  <?php echo form_open(current_url()); ?>
    <div class="mb-3">
      <label class="form-label">Tên Ba/Mẹ</label>
      <input type="text" name="parent_name" class="form-control" required value="<?php echo htmlspecialchars($reg['parent_name']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Số điện thoại</label>
      <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($reg['phone']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Số lượng bé</label>
      <input type="number" name="kid_count" class="form-control" min="1" required value="<?php echo (int) $reg['kid_count']; ?>">
      <div class="form-text">Không giới hạn số bé khi quản trị viên chỉnh sửa (form đăng ký công khai giới hạn tối đa 3 bé).</div>
    </div>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg">Lưu</button>
      <a href="<?php echo site_url('me/trung-thu/admin'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
