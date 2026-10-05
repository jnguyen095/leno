<div class="container py-3 py-md-4" style="max-width:600px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Nhập doanh thu — <?php echo payroll_period_label($period); ?></h4>
    <a href="<?php echo site_url('me/reports').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
  </div>

  <?php echo form_open('me/reports/entry', array('class' => 'card border-0 shadow-sm rounded-4')); ?>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Tháng</label>
        <input type="month" name="period" class="form-control" style="max-width:200px;" value="<?php echo $period; ?>" onchange="location.href='<?php echo site_url('me/reports/entry'); ?>?period='+this.value">
      </div>

      <?php foreach (Monthly_revenue_model::CATEGORIES as $category): $row = isset($existing[$category]) ? $existing[$category] : NULL; ?>
        <div class="mb-3 pb-3 border-bottom">
          <label class="form-label fw-semibold">
            <span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:<?php echo revenue_category_color($category); ?>;"></span>
            <?php echo revenue_category_label($category); ?>
          </label>
          <input type="number" step="1000" min="0" name="revenue_<?php echo $category; ?>" class="form-control" placeholder="0" value="<?php echo $row ? (float) $row['revenue'] : ''; ?>">
          <input type="text" name="note_<?php echo $category; ?>" class="form-control form-control-sm mt-1" placeholder="Ghi chú (tuỳ chọn)" value="<?php echo $row ? htmlspecialchars((string) $row['note']) : ''; ?>">
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer bg-white border-0 p-3 pt-0">
      <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu doanh thu</button>
    </div>
  <?php echo form_close(); ?>
</div>
