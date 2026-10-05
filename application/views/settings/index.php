<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-gear"></i> Cài đặt hệ thống</h4>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white fw-semibold">Thuế VAT</div>
    <div class="card-body">
      <?php echo form_open(current_url()); ?>
        <div class="mb-3">
          <label class="form-label">Thuế suất VAT (%)</label>
          <div class="input-group">
            <input type="number" name="vat_percent" class="form-control form-control-lg" min="0" max="100" step="0.1"
                   value="<?php echo $vat_percent; ?>" required>
            <span class="input-group-text">%</span>
          </div>
          <div class="form-text">Áp dụng cho tất cả đơn hàng (tại bàn, mang đi) kể từ lần tính lại tổng tiền tiếp theo.</div>
        </div>
        <button class="btn btn-brand btn-lg w-100">Lưu thay đổi</button>
      <?php echo form_close(); ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-3">
    <div class="card-header bg-white fw-semibold">Bán mang đi</div>
    <div class="card-body">
      <?php echo form_open(current_url()); ?>
        <input type="hidden" name="form" value="takeaway">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" role="switch" name="takeaway_enabled" value="1" id="takeawayEnabled"
                 <?php echo $takeaway_enabled ? 'checked' : ''; ?> onchange="this.form.submit()">
          <label class="form-check-label" for="takeawayEnabled">Bật bán mang đi</label>
        </div>
        <div class="form-text">Khi bật, Sơ đồ bàn có thêm bàn "Mang đi" — mở, gọi món và thanh toán giống bàn thường.</div>
        <noscript><button class="btn btn-brand w-100 mt-2">Lưu thay đổi</button></noscript>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
