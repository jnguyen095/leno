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
    <div class="card-header bg-white fw-semibold">Thông tin in phiếu</div>
    <div class="card-body">
      <?php echo form_open(current_url()); ?>
        <input type="hidden" name="form" value="receipt">
        <div class="mb-3">
          <label class="form-label">Tên quán</label>
          <input type="text" name="receipt_shop_name" class="form-control form-control-lg" maxlength="60" required
                 value="<?php echo htmlspecialchars($receipt['shop_name']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Địa chỉ</label>
          <input type="text" name="receipt_address" class="form-control form-control-lg" maxlength="120"
                 value="<?php echo htmlspecialchars($receipt['address']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Số điện thoại <span class="text-muted small">(không bắt buộc)</span></label>
          <input type="text" name="receipt_phone" class="form-control form-control-lg" maxlength="30" inputmode="tel"
                 value="<?php echo htmlspecialchars($receipt['phone']); ?>">
          <div class="form-text">Địa chỉ và số điện thoại in chung một dòng dưới tên quán.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Lời cảm ơn cuối hóa đơn</label>
          <input type="text" name="receipt_footer" class="form-control form-control-lg" maxlength="120"
                 value="<?php echo htmlspecialchars($receipt['footer']); ?>">
        </div>
        <button class="btn btn-brand btn-lg w-100">Lưu thay đổi</button>
      <?php echo form_close(); ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-3">
    <div class="card-header bg-white fw-semibold">Chuyển khoản (mã VietQR)</div>
    <div class="card-body">
      <?php echo form_open(current_url()); ?>
        <input type="hidden" name="form" value="bank_qr">
        <div class="mb-3">
          <label class="form-label">Ngân hàng</label>
          <select name="bank_qr_bin" class="form-select form-select-lg">
            <option value="">— Chọn ngân hàng —</option>
            <?php foreach ($vietqr_banks as $bin => $name): ?>
              <option value="<?php echo $bin; ?>" <?php echo $bank_qr['bin'] === (string) $bin ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Số tài khoản</label>
          <input type="text" name="bank_qr_account_no" class="form-control form-control-lg" inputmode="numeric" maxlength="19"
                 value="<?php echo htmlspecialchars($bank_qr['account_no']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Tên chủ tài khoản <span class="text-muted small">(không bắt buộc)</span></label>
          <input type="text" name="bank_qr_account_name" class="form-control form-control-lg" maxlength="100"
                 value="<?php echo htmlspecialchars($bank_qr['account_name']); ?>" placeholder="VD: NGUYEN VAN A">
          <div class="form-text">In dưới mã QR để khách đối chiếu trước khi chuyển.</div>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" role="switch" name="bank_qr_enabled" value="1" id="bankQrEnabled"
                 <?php echo $bank_qr_on ? 'checked' : ''; ?>>
          <label class="form-check-label" for="bankQrEnabled">In mã QR chuyển khoản trên phiếu tạm tính</label>
        </div>
        <div class="form-text mb-3">Ứng dụng POS tạo mã VietQR cho từng phiếu: đúng số tiền, nội dung chuyển khoản là số hóa đơn.</div>
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
