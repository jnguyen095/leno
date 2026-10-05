<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-union"></i> Gộp bàn <?php echo htmlspecialchars($table['table_name']); ?></h4>
  <p class="text-muted small">Toàn bộ món của bàn này sẽ được gộp vào hóa đơn của bàn đích. Bàn hiện tại sẽ đóng phiên và trở thành bàn trống.</p>

  <?php echo form_open(current_url()); ?>
    <div class="mb-3">
      <label class="form-label">Gộp vào bàn</label>
      <select name="target_table_id" class="form-select form-select-lg" required>
        <option value="">-- Chọn bàn --</option>
        <?php foreach ($others as $t): ?>
          <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['table_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg" type="submit">Xác nhận gộp bàn</button>
      <a href="<?php echo site_url('me/tables'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
