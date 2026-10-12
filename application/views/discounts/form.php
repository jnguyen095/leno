<?php
  // Thêm / sửa khuyến mãi: mức giảm (% hoặc số tiền, giới hạn tối đa, đơn tối thiểu) + thời gian áp dụng.
  $is_pct = $values['discount_type'] === 'PERCENT';
?>
<div class="container-xl py-3 py-md-4 settings-page" style="max-width:860px">
  <a href="<?php echo site_url('me/discounts'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Khuyến mãi</a>
  <h4 class="fw-bold mb-3"><?php echo $discount ? 'Sửa khuyến mãi' : 'Thêm khuyến mãi'; ?></h4>

  <?php if ($error): ?>
    <div class="alert alert-danger d-flex gap-2 align-items-start py-2 small"><i class="bi bi-exclamation-triangle-fill"></i><div><?php echo htmlspecialchars($error); ?></div></div>
  <?php endif; ?>

  <?php echo form_open(current_url(), array('id' => 'dcForm')); ?>
  <?php // ---------- Mức giảm ---------- ?>
  <section class="settings-card">
    <header class="settings-card-head">
      <span class="settings-card-icon"><i class="bi bi-percent"></i></span>
      <div><h5>Mức giảm</h5><p>Giảm trên tạm tính của đơn (sau khi áp bảng giá).</p></div>
    </header>
    <div class="settings-card-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="dcName">Tên khuyến mãi</label>
          <input type="text" name="name" id="dcName" class="form-control" maxlength="100" required placeholder="VD: Giảm 15% đơn từ 500K"
                 value="<?php echo htmlspecialchars($values['name']); ?>">
        </div>
        <div class="col-12">
          <label class="form-label d-block">Kiểu giảm</label>
          <div class="pos-seg dc-type" role="group">
            <input type="radio" class="btn-check" name="discount_type" value="PERCENT" id="dcPct" <?php echo $is_pct ? 'checked' : ''; ?>>
            <label class="pos-seg-item" for="dcPct"><i class="bi bi-percent"></i> Theo phần trăm</label>
            <input type="radio" class="btn-check" name="discount_type" value="AMOUNT" id="dcAmt" <?php echo $is_pct ? '' : 'checked'; ?>>
            <label class="pos-seg-item" for="dcAmt"><i class="bi bi-cash"></i> Số tiền cố định</label>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="dcValue"><span class="js-pct">Phần trăm giảm</span><span class="js-amt">Số tiền giảm</span></label>
          <div class="input-group">
            <input type="text" name="value" id="dcValue" class="form-control" inputmode="decimal" required value="<?php echo htmlspecialchars($values['value']); ?>">
            <span class="input-group-text"><span class="js-pct">%</span><span class="js-amt">đ</span></span>
          </div>
        </div>
        <div class="col-md-4 js-pct">
          <label class="form-label" for="dcMax">Giảm tối đa <span class="text-muted small">(tuỳ chọn)</span></label>
          <div class="input-group">
            <input type="text" name="max_discount" id="dcMax" class="form-control js-money" inputmode="numeric" placeholder="Không giới hạn" value="<?php echo htmlspecialchars($values['max_discount']); ?>">
            <span class="input-group-text">đ</span>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="dcMin">Đơn tối thiểu <span class="text-muted small">(tuỳ chọn)</span></label>
          <div class="input-group">
            <input type="text" name="min_order" id="dcMin" class="form-control js-money" inputmode="numeric" placeholder="Mọi đơn" value="<?php echo htmlspecialchars($values['min_order']); ?>">
            <span class="input-group-text">đ</span>
          </div>
        </div>
      </div>
      <div class="dc-example mt-3" id="dcExample"></div>
    </div>
  </section>

  <?php // ---------- Thời gian áp dụng (giống bảng giá) ---------- ?>
  <section class="settings-card">
    <header class="settings-card-head">
      <span class="settings-card-icon"><i class="bi bi-calendar-week"></i></span>
      <div><h5>Thời gian áp dụng</h5><p>Khuyến mãi chỉ hiện trên POS khi đang bật, trong khoảng ngày và đúng thứ áp dụng.</p></div>
    </header>
    <div class="settings-card-body">
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <label class="form-label" for="dcStart">Ngày bắt đầu</label>
          <input type="date" name="start_date" id="dcStart" class="form-control" required value="<?php echo htmlspecialchars($values['start_date']); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="dcEnd">Ngày kết thúc <span class="text-muted small">(tuỳ chọn)</span></label>
          <input type="date" name="end_date" id="dcEnd" class="form-control" value="<?php echo htmlspecialchars($values['end_date']); ?>">
        </div>
        <div class="col-12">
          <label class="form-label d-block">Áp dụng vào</label>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="pb-days" role="group" aria-label="Thứ áp dụng">
              <?php foreach (Price_book_model::WEEKDAYS as $d => $label): ?>
                <input type="checkbox" class="btn-check" name="weekdays[]" value="<?php echo $d; ?>" id="dcDay<?php echo $d; ?>" autocomplete="off"
                       <?php echo in_array($d, $values['weekdays'], TRUE) ? 'checked' : ''; ?>>
                <label class="pb-day" for="dcDay<?php echo $d; ?>"><?php echo $label; ?></label>
              <?php endforeach; ?>
            </div>
            <div class="d-flex gap-1">
              <button type="button" class="orders-chip" data-days="1,2,3,4,5,6,7">Mọi ngày</button>
              <button type="button" class="orders-chip" data-days="1,2,3,4,5">Ngày thường</button>
              <button type="button" class="orders-chip" data-days="6,7">Cuối tuần</button>
            </div>
          </div>
        </div>
      </div>
      <div class="settings-row settings-row-flush mt-3">
        <div class="settings-row-text">
          <label class="settings-row-title" for="dcStatus">Bật khuyến mãi</label>
          <div class="settings-row-desc">Tắt để tạm ngưng mà không cần xoá.</div>
        </div>
        <div class="settings-row-control">
          <div class="form-check form-switch settings-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" name="status" value="1" id="dcStatus" <?php echo $values['status'] === 'ACTIVE' ? 'checked' : ''; ?>>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="pb-savebar">
    <a href="<?php echo site_url('me/discounts'); ?>" class="btn btn-outline-secondary">Huỷ</a>
    <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu khuyến mãi</button>
  </div>
  <?php echo form_close(); ?>
</div>

<script>
(function(){
  var fmt = function(n){ return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
  var num = function(id){ var s = document.getElementById(id).value.replace(/[\s.]/g, '').replace(',', '.'); return s === '' ? null : parseFloat(s); };
  var money = function(id){ var s = document.getElementById(id).value.replace(/[\s.,]/g, ''); return /^\d+$/.test(s) ? parseInt(s, 10) : null; };
  var isPct = function(){ return document.getElementById('dcPct').checked; };

  // Ví dụ trực quan: một đơn mẫu được giảm bao nhiêu.
  function example(){
    var pct = isPct(), v = pct ? num('dcValue') : money('dcValue'), max = money('dcMax'), min = money('dcMin') || 0, el = document.getElementById('dcExample');
    document.querySelectorAll('.js-pct').forEach(function(e){ e.classList.toggle('d-none', ! pct); });
    document.querySelectorAll('.js-amt').forEach(function(e){ e.classList.toggle('d-none', pct); });
    if ( ! (v > 0)) { el.innerHTML = '<i class="bi bi-lightbulb"></i> Nhập mức giảm để xem ví dụ.'; return; }
    var sample = Math.max(min, pct ? 300000 : 200000);
    sample = Math.ceil(sample / 50000) * 50000 || 200000;
    var d = pct ? Math.round(sample * v / 100) : v;
    if (pct && max) d = Math.min(d, max);
    d = Math.min(d, sample);
    el.innerHTML = '<i class="bi bi-lightbulb"></i> Ví dụ: đơn <b>' + fmt(sample) + 'đ</b> → giảm <b class="text-success">' + fmt(d) + 'đ</b>, khách trả <b>' + fmt(sample - d) + 'đ</b>'
      + (min ? '. Đơn dưới ' + fmt(min) + 'đ không được giảm.' : '.');
  }
  document.querySelectorAll('#dcForm input').forEach(function(i){ i.addEventListener('input', example); i.addEventListener('change', example); });
  // Số tiền: tự thêm dấu chấm hàng nghìn khi rời ô.
  document.querySelectorAll('.js-money').forEach(function(i){
    i.addEventListener('blur', function(){ var s = i.value.replace(/[\s.,]/g, ''); if (/^\d+$/.test(s)) i.value = fmt(parseInt(s, 10)); });
  });
  document.getElementById('dcValue').addEventListener('blur', function(){
    if (isPct()) return; var s = this.value.replace(/[\s.,]/g, ''); if (/^\d+$/.test(s)) this.value = fmt(parseInt(s, 10));
  });
  document.querySelectorAll('[data-days]').forEach(function(b){
    b.addEventListener('click', function(){
      var days = b.dataset.days.split(',');
      document.querySelectorAll('input[name="weekdays[]"]').forEach(function(c){ c.checked = days.indexOf(c.value) !== -1; });
    });
  });
  example();
})();
</script>
