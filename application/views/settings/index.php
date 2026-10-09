<?php
  // Cài đặt hệ thống — máy tính: menu mục bên trái (dính khi cuộn) + các thẻ cài đặt bên phải;
  // điện thoại: dãy mục trượt ngang dính trên đầu, thẻ xếp dọc. Mỗi thẻ là một form riêng.
  $sections = array(
    'sales'   => array('bi-shop', 'Bán hàng', 'Thuế VAT, bán mang đi'),
    'receipt' => array('bi-receipt', 'Thông tin in phiếu', 'Tên quán, địa chỉ, lời cảm ơn'),
    'bank'    => array('bi-bank', 'Chuyển khoản', 'Mã VietQR trên phiếu tạm tính'),
  );
  // Thông báo (lỗi / đã lưu) hiện ngay trong mục liên quan.
  $alert = function ($section) use ($error, $error_section, $success, $success_section) {
    if ($error && $error_section === $section)
      return '<div class="alert alert-danger d-flex gap-2 align-items-start py-2 small mb-3"><i class="bi bi-exclamation-triangle-fill"></i><div>'.$error.'</div></div>';
    if ($success && $success_section === $section)
      return '<div class="alert alert-success d-flex gap-2 align-items-center py-2 small mb-3 settings-saved"><i class="bi bi-check-circle-fill"></i><div>'.htmlspecialchars($success).'</div></div>';
    return '';
  };
?>
<div class="container-xl py-3 py-md-4 settings-page">
  <div class="mb-3 mb-lg-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-gear"></i> Cài đặt hệ thống</h4>
    <div class="text-muted small">Áp dụng cho toàn bộ cửa hàng — trang web và ứng dụng POS.</div>
  </div>

  <div class="row g-3 g-lg-4">
    <?php // Menu mục: cột trái (máy tính) / dãy trượt ngang dính trên đầu (điện thoại). ?>
    <div class="col-lg-3 settings-nav-col">
      <nav class="settings-nav" id="settingsNav" aria-label="Các mục cài đặt">
        <?php foreach ($sections as $id => $s): ?>
          <a class="settings-nav-link" href="#<?php echo $id; ?>" data-section="<?php echo $id; ?>">
            <i class="bi <?php echo $s[0]; ?>"></i>
            <span><span class="settings-nav-title"><?php echo $s[1]; ?></span><small class="settings-nav-desc"><?php echo $s[2]; ?></small></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>

    <div class="col-lg-9">
      <?php // ---------- Bán hàng ---------- ?>
      <section class="settings-card" id="sales">
        <header class="settings-card-head">
          <span class="settings-card-icon"><i class="bi bi-shop"></i></span>
          <div><h5>Bán hàng</h5><p>Thuế áp dụng cho đơn hàng và bàn "Mang đi" trên sơ đồ bàn.</p></div>
        </header>
        <div class="settings-card-body">
          <?php echo $alert('sales'); ?>
          <?php echo form_open(current_url(), array('class' => 'settings-row')); ?>
            <div class="settings-row-text">
              <label class="settings-row-title" for="vatPercent">Thuế suất VAT</label>
              <div class="settings-row-desc">Áp dụng cho mọi đơn (tại bàn, mang đi) từ lần tính lại tổng tiền tiếp theo. Nhập 0 nếu không tính VAT.</div>
            </div>
            <div class="settings-row-control d-flex gap-2">
              <div class="input-group settings-vat">
                <input type="number" name="vat_percent" id="vatPercent" class="form-control" min="0" max="100" step="0.1"
                       value="<?php echo $vat_percent; ?>" required>
                <span class="input-group-text">%</span>
              </div>
              <button class="btn btn-brand">Lưu</button>
            </div>
          <?php echo form_close(); ?>

          <?php echo form_open(current_url(), array('class' => 'settings-row')); ?>
            <input type="hidden" name="form" value="takeaway">
            <div class="settings-row-text">
              <label class="settings-row-title" for="takeawayEnabled">Bán mang đi</label>
              <div class="settings-row-desc">Sơ đồ bàn có thêm bàn "Mang đi" — mở, gọi món và thanh toán giống bàn thường.</div>
            </div>
            <div class="settings-row-control">
              <div class="form-check form-switch settings-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="takeaway_enabled" value="1" id="takeawayEnabled"
                       <?php echo $takeaway_enabled ? 'checked' : ''; ?> onchange="this.form.submit()">
              </div>
              <noscript><button class="btn btn-brand btn-sm ms-2">Lưu</button></noscript>
            </div>
          <?php echo form_close(); ?>
        </div>
      </section>

      <?php // ---------- Thông tin in phiếu ---------- ?>
      <section class="settings-card" id="receipt">
        <header class="settings-card-head">
          <span class="settings-card-icon"><i class="bi bi-receipt"></i></span>
          <div><h5>Thông tin in phiếu</h5><p>In ở đầu và cuối phiếu tạm tính, hóa đơn.</p></div>
        </header>
        <?php echo form_open(current_url()); ?>
        <input type="hidden" name="form" value="receipt">
        <div class="settings-card-body">
          <?php echo $alert('receipt'); ?>
          <div class="row g-4">
            <div class="col-xl-7">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="rcName">Tên quán</label>
                  <input type="text" name="receipt_shop_name" id="rcName" class="form-control" maxlength="60" required
                         value="<?php echo htmlspecialchars($receipt['shop_name']); ?>" data-preview="name">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="rcPhone">Số điện thoại <span class="text-muted small">(không bắt buộc)</span></label>
                  <input type="text" name="receipt_phone" id="rcPhone" class="form-control" maxlength="30" inputmode="tel"
                         value="<?php echo htmlspecialchars($receipt['phone']); ?>" data-preview="contact">
                </div>
                <div class="col-12">
                  <label class="form-label" for="rcAddress">Địa chỉ</label>
                  <input type="text" name="receipt_address" id="rcAddress" class="form-control" maxlength="120"
                         value="<?php echo htmlspecialchars($receipt['address']); ?>" data-preview="contact">
                  <div class="form-text">Địa chỉ và số điện thoại in chung một dòng dưới tên quán.</div>
                </div>
                <div class="col-12">
                  <label class="form-label" for="rcFooter">Lời cảm ơn cuối hóa đơn</label>
                  <input type="text" name="receipt_footer" id="rcFooter" class="form-control" maxlength="120"
                         value="<?php echo htmlspecialchars($receipt['footer']); ?>" data-preview="footer">
                </div>
              </div>
            </div>
            <?php // Xem trước đầu/cuối phiếu — cập nhật ngay khi gõ. ?>
            <div class="col-xl-5">
              <div class="settings-preview-label">Xem trước</div>
              <div class="settings-preview">
                <div class="receipt-k80">
                  <div class="center bold rk-shop" id="pvName"></div>
                  <div class="center" id="pvContact"></div>
                  <div class="center bold rk-title">PHIẾU TÍNH TIỀN</div>
                  <hr>
                  <div class="settings-preview-skip">· · ·</div>
                  <hr>
                  <div class="center" id="pvFooter"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <footer class="settings-card-foot">
          <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu thay đổi</button>
        </footer>
        <?php echo form_close(); ?>
      </section>

      <?php // ---------- Chuyển khoản ---------- ?>
      <section class="settings-card" id="bank">
        <header class="settings-card-head">
          <span class="settings-card-icon"><i class="bi bi-bank"></i></span>
          <div><h5>Chuyển khoản (mã VietQR)</h5><p>Mã QR đúng số tiền, nội dung là số hóa đơn — in trên phiếu tạm tính (web và ứng dụng POS).</p></div>
        </header>
        <?php echo form_open(current_url()); ?>
        <input type="hidden" name="form" value="bank_qr">
        <div class="settings-card-body">
          <?php echo $alert('bank'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="bqBank">Ngân hàng</label>
              <select name="bank_qr_bin" id="bqBank" class="form-select">
                <option value="">— Chọn ngân hàng —</option>
                <?php foreach ($vietqr_banks as $bin => $name): ?>
                  <option value="<?php echo $bin; ?>" <?php echo $bank_qr['bin'] === (string) $bin ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="bqAccount">Số tài khoản</label>
              <input type="text" name="bank_qr_account_no" id="bqAccount" class="form-control" inputmode="numeric" maxlength="19"
                     value="<?php echo htmlspecialchars($bank_qr['account_no']); ?>">
            </div>
            <div class="col-12">
              <label class="form-label" for="bqName">Tên chủ tài khoản <span class="text-muted small">(không bắt buộc)</span></label>
              <input type="text" name="bank_qr_account_name" id="bqName" class="form-control" maxlength="100"
                     value="<?php echo htmlspecialchars($bank_qr['account_name']); ?>" placeholder="VD: NGUYEN VAN A">
              <div class="form-text">In dưới mã QR để khách đối chiếu trước khi chuyển.</div>
            </div>
          </div>
          <div class="settings-row settings-row-flush mt-3">
            <div class="settings-row-text">
              <label class="settings-row-title" for="bankQrEnabled">In mã QR trên phiếu tạm tính</label>
              <div class="settings-row-desc">Cần chọn ngân hàng và nhập số tài khoản trước khi bật.</div>
            </div>
            <div class="settings-row-control">
              <div class="form-check form-switch settings-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="bank_qr_enabled" value="1" id="bankQrEnabled"
                       <?php echo $bank_qr_on ? 'checked' : ''; ?>>
              </div>
            </div>
          </div>
        </div>
        <footer class="settings-card-foot">
          <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu thay đổi</button>
        </footer>
        <?php echo form_close(); ?>
      </section>
    </div>
  </div>
</div>

<script>
(function(){
  // Xem trước thông tin in phiếu.
  var val = function(id){ return document.getElementById(id).value.trim(); };
  function preview(){
    document.getElementById('pvName').textContent = val('rcName') || 'Tên quán';
    document.getElementById('pvContact').textContent = [val('rcAddress'), val('rcPhone')].filter(Boolean).join(' - ');
    document.getElementById('pvFooter').textContent = val('rcFooter');
  }
  document.querySelectorAll('[data-preview]').forEach(function(el){ el.addEventListener('input', preview); });
  preview();

  // Menu mục dính ngay dưới thanh điều hướng (cao khác nhau trên máy tính / điện thoại).
  var page = document.querySelector('.settings-page'), bar = document.querySelector('.navbar.sticky-top');
  function setTop(){ page.style.setProperty('--settings-top', ((bar ? bar.offsetHeight : 56) + (window.innerWidth >= 992 ? 16 : 0)) + 'px'); }
  setTop(); window.addEventListener('resize', setTop);

  // Menu mục: tô sáng mục đang xem khi cuộn; trên điện thoại cuộn dãy mục để mục đang xem luôn hiện.
  var links = document.querySelectorAll('.settings-nav-link');
  function activate(id){
    links.forEach(function(a){
      var on = a.dataset.section === id;
      a.classList.toggle('active', on);
      if (on && a.parentNode.scrollWidth > a.parentNode.clientWidth) {
        a.parentNode.scrollTo({ left: a.offsetLeft - 16, behavior: 'smooth' });
      }
    });
  }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ if (e.isIntersecting) activate(e.target.id); });
    }, { rootMargin: '-35% 0px -60% 0px' });
    document.querySelectorAll('.settings-card').forEach(function(s){ io.observe(s); });
  }
  activate((location.hash || '#sales').slice(1));
  // Ẩn dần thông báo "Đã lưu" sau vài giây.
  setTimeout(function(){ document.querySelectorAll('.settings-saved').forEach(function(el){ el.classList.add('fade-out'); }); }, 4000);
})();
</script>
