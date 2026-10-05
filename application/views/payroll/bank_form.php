<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-bank"></i> Thông tin ngân hàng</h4>
  <div class="form-text mb-3">Dùng để nhận lương qua chuyển khoản. Chỉ mình bạn xem/sửa được thông tin này.</div>

  <?php if ($success): ?>
    <div class="alert alert-success py-2 small">Đã lưu thông tin ngân hàng.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <?php echo form_open(current_url()); ?>
        <div class="mb-3">
          <label class="form-label">Ngân hàng</label>
          <input type="text" name="bank_name" class="form-control form-control-lg" placeholder="VD: Vietcombank" value="<?php echo htmlspecialchars((string) $bank_info['bank_name']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Số tài khoản</label>
          <input type="text" name="bank_account_number" class="form-control form-control-lg" inputmode="numeric" value="<?php echo htmlspecialchars((string) $bank_info['bank_account_number']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Chủ tài khoản</label>
          <input type="text" name="bank_account_name" class="form-control form-control-lg" placeholder="Ghi đúng như trên thẻ/tài khoản" value="<?php echo htmlspecialchars((string) $bank_info['bank_account_name']); ?>">
        </div>
        <button class="btn btn-brand btn-lg w-100">Lưu thông tin</button>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
