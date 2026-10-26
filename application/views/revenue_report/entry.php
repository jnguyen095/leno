<div class="container py-3 py-md-4" style="max-width:600px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Nhập doanh thu — <?php echo payroll_period_label($period); ?></h4>
    <a href="<?php echo site_url('me/reports/revenue').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
  </div>

  <?php if ( ! empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php echo form_open('me/reports/revenue/entry', array('class' => 'card border-0 shadow-sm rounded-4')); ?>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Tháng</label>
        <input type="month" name="period" class="form-control" style="max-width:200px;" value="<?php echo $period; ?>" onchange="location.href='<?php echo site_url('me/reports/revenue/entry'); ?>?period='+this.value">
      </div>

      <?php foreach (Monthly_revenue_model::CATEGORIES as $category): $row = isset($existing[$category]) ? $existing[$category] : NULL; ?>
        <div class="mb-3 pb-3 border-bottom">
          <label class="form-label fw-semibold">
            <span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:<?php echo revenue_category_color($category); ?>;"></span>
            <?php echo revenue_category_label($category); ?>
          </label>
          <?php // Ô chữ (không dùng type=number step=1000: số lẻ nghìn sẽ bị trình duyệt chặn) — tự thêm dấu chấm hàng nghìn khi rời ô. ?>
          <?php $posted = $this->input->post('revenue_'.$category); ?>
          <div class="input-group">
            <input type="text" inputmode="numeric" maxlength="20" name="revenue_<?php echo $category; ?>" class="form-control js-money" placeholder="0"
                   value="<?php echo htmlspecialchars($posted !== NULL ? (string) $posted : ($row ? number_format((float) $row['revenue'], 0, ',', '.') : '')); ?>">
            <span class="input-group-text">đ</span>
          </div>
          <input type="text" name="note_<?php echo $category; ?>" class="form-control form-control-sm mt-1" placeholder="Ghi chú (tuỳ chọn)" value="<?php echo $row ? htmlspecialchars((string) $row['note']) : ''; ?>">
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer bg-white border-0 p-3 pt-0">
      <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu doanh thu</button>
    </div>
  <?php echo form_close(); ?>
</div>
<script>
// Ô số tiền: tự thêm dấu chấm hàng nghìn khi rời ô (12500000 -> 12.500.000).
document.querySelectorAll('.js-money').forEach(function(i){
  i.addEventListener('blur', function(){
    var s = i.value.replace(/[\s.,]/g, '');
    if (/^\d+$/.test(s)) i.value = s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  });
});
</script>
