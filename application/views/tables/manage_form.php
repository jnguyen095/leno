<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><?php echo $page_title; ?></h4>
  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
  <?php echo form_open(current_url()); ?>
    <div class="mb-3">
      <label class="form-label">Mã bàn</label>
      <input type="text" name="table_code" class="form-control" required maxlength="20"
             value="<?php echo $table ? htmlspecialchars($table['table_code']) : ''; ?>" placeholder="VD: T13">
    </div>
    <div class="mb-3">
      <label class="form-label">Tên bàn</label>
      <input type="text" name="table_name" class="form-control" required maxlength="100"
             value="<?php echo $table ? htmlspecialchars($table['table_name']) : ''; ?>" placeholder="VD: Bàn 13">
    </div>
    <div class="mb-3">
      <label class="form-label">Ghi chú bàn</label>
      <input type="text" name="note" class="form-control" maxlength="255"
             value="<?php echo $table ? htmlspecialchars((string) $table['note']) : ''; ?>" placeholder="VD: Gần cửa sổ, ghế hỏng 1 cái">
      <div class="form-text">Hiện trên sơ đồ bàn và trang gọi món, giữ nguyên qua các lượt khách.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Sức chứa (số chỗ ngồi)</label>
      <input type="number" name="capacity" class="form-control" required min="1" max="50"
             value="<?php echo $table ? (int) $table['capacity'] : 4; ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Thứ tự hiển thị</label>
      <input type="number" name="sort_order" class="form-control" value="<?php echo $table ? (int) $table['sort_order'] : 0; ?>">
      <div class="form-text">Số nhỏ hơn hiện trước — dùng để sắp xếp thứ tự bàn trong sơ đồ.</div>
    </div>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg">Lưu</button>
      <a href="<?php echo site_url('me/tables/manage'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
