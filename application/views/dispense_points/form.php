<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><?php echo $page_title; ?></h4>
  <?php echo form_open(current_url()); ?>
    <div class="mb-3">
      <label class="form-label">Tên điểm xuất kho</label>
      <input type="text" name="name" class="form-control" required value="<?php echo $dispense_point ? htmlspecialchars($dispense_point['name']) : ''; ?>">
    </div>
    <?php if ($dispense_point): ?>
    <div class="mb-3">
      <label class="form-label">Trạng thái</label>
      <select name="status" class="form-select">
        <option value="ACTIVE" <?php echo $dispense_point['status']==='ACTIVE'?'selected':''; ?>>Hoạt động</option>
        <option value="INACTIVE" <?php echo $dispense_point['status']==='INACTIVE'?'selected':''; ?>>Ẩn</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg">Lưu</button>
      <a href="<?php echo site_url('me/inventory/dispense-points'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
