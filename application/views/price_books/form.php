<?php
  // Thêm / sửa bảng giá: thông tin (tên, ngày, thứ áp dụng, bật/tắt) + bảng giá mới từng món.
  // Giá mới để trống = giữ giá gốc. Công cụ "Điều chỉnh nhanh" điền giá theo % hoặc số tiền cho cả danh mục.
  $money = function ($v) { return number_format((float) $v, 0, ',', '.'); };
?>
<div class="container-xl py-3 py-md-4 settings-page">
  <a href="<?php echo site_url('me/price-books'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Bảng giá</a>
  <h4 class="fw-bold mb-3"><?php echo $book ? 'Sửa bảng giá' : 'Thêm bảng giá'; ?></h4>

  <?php if ($error): ?>
    <div class="alert alert-danger d-flex gap-2 align-items-start py-2 small"><i class="bi bi-exclamation-triangle-fill"></i><div><?php echo htmlspecialchars($error); ?></div></div>
  <?php endif; ?>

  <?php echo form_open(current_url(), array('id' => 'pbForm')); ?>
  <?php // ---------- Thông tin ---------- ?>
  <section class="settings-card">
    <header class="settings-card-head">
      <span class="settings-card-icon"><i class="bi bi-info-circle"></i></span>
      <div><h5>Thông tin bảng giá</h5><p>Bảng giá chỉ hiện trên POS khi đang bật, trong khoảng ngày và đúng thứ áp dụng.</p></div>
    </header>
    <div class="settings-card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="pbName">Tên bảng giá</label>
          <input type="text" name="name" id="pbName" class="form-control" maxlength="100" required placeholder="VD: Giá cuối tuần, Ưu đãi phụ nữ"
                 value="<?php echo htmlspecialchars($values['name']); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="pbStart">Ngày bắt đầu</label>
          <input type="date" name="start_date" id="pbStart" class="form-control" required value="<?php echo htmlspecialchars($values['start_date']); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="pbEnd">Ngày kết thúc <span class="text-muted small">(tuỳ chọn)</span></label>
          <input type="date" name="end_date" id="pbEnd" class="form-control" value="<?php echo htmlspecialchars($values['end_date']); ?>">
        </div>
        <div class="col-12">
          <label class="form-label d-block">Áp dụng vào</label>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="pb-days" role="group" aria-label="Thứ áp dụng">
              <?php foreach (Price_book_model::WEEKDAYS as $d => $label): ?>
                <input type="checkbox" class="btn-check" name="weekdays[]" value="<?php echo $d; ?>" id="pbDay<?php echo $d; ?>" autocomplete="off"
                       <?php echo in_array($d, $values['weekdays'], TRUE) ? 'checked' : ''; ?>>
                <label class="pb-day" for="pbDay<?php echo $d; ?>"><?php echo $label; ?></label>
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
          <label class="settings-row-title" for="pbStatus">Bật bảng giá</label>
          <div class="settings-row-desc">Tắt để tạm ngưng mà không cần xoá.</div>
        </div>
        <div class="settings-row-control">
          <div class="form-check form-switch settings-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" name="status" value="1" id="pbStatus" <?php echo $values['status'] === 'ACTIVE' ? 'checked' : ''; ?>>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php // ---------- Giá từng món ---------- ?>
  <section class="settings-card">
    <header class="settings-card-head">
      <span class="settings-card-icon"><i class="bi bi-cash-coin"></i></span>
      <div><h5>Giá mới từng món</h5><p>Để trống = giữ giá gốc. Có thể tăng hoặc giảm so với giá gốc.</p></div>
    </header>
    <div class="settings-card-body">
      <?php // Điều chỉnh nhanh: điền giá mới cho cả danh mục theo % hoặc số tiền, rồi sửa tay từng món nếu cần. ?>
      <div class="pb-bulk">
        <div class="fw-semibold small mb-2"><i class="bi bi-magic"></i> Điều chỉnh nhanh</div>
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label">Danh mục</label>
            <select id="bulkCat" class="form-select form-select-sm">
              <option value="all">Tất cả món</option>
              <?php $ci = 0; foreach (array_keys($products_by_category) as $cat): ?>
                <option value="<?php echo $ci++; ?>"><?php echo htmlspecialchars($cat); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-4 col-md-2">
            <label class="form-label">Kiểu</label>
            <select id="bulkDir" class="form-select form-select-sm"><option value="-1">Giảm</option><option value="1">Tăng</option></select>
          </div>
          <div class="col-4 col-md-2">
            <label class="form-label">Mức</label>
            <input type="number" id="bulkVal" class="form-control form-control-sm" min="0" step="1" placeholder="10">
          </div>
          <div class="col-4 col-md-2">
            <label class="form-label">Đơn vị</label>
            <select id="bulkUnit" class="form-select form-select-sm"><option value="pct">%</option><option value="amt">đồng</option></select>
          </div>
          <div class="col-12 col-md-2 d-flex gap-2">
            <button type="button" class="btn btn-sm btn-brand flex-grow-1" id="bulkApply">Áp dụng</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkClear" title="Xoá giá mới của danh mục đang chọn"><i class="bi bi-eraser"></i></button>
          </div>
        </div>
        <div class="form-text">Giá theo % được làm tròn tới 1.000đ. Áp dụng xong vẫn sửa tay được từng món.</div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between my-3">
        <div class="pos-search m-0" style="max-width:320px;flex:1 1 220px">
          <i class="bi bi-search"></i>
          <input type="search" id="pbSearch" class="form-control" placeholder="Tìm món (vd: ca phe sua)" autocomplete="off">
        </div>
        <div class="small text-muted"><span id="pbCount">0</span> món có giá mới</div>
      </div>

      <?php if (empty($products_by_category)): ?>
        <div class="text-muted text-center py-4">Chưa có món nào đang bán.</div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="table align-middle mb-0 pb-table">
          <thead><tr><th>Món</th><th class="text-end">Giá gốc</th><th class="pb-new-col">Giá mới</th><th class="text-end pb-diff-col">Chênh lệch</th></tr></thead>
          <?php $ci = 0; foreach ($products_by_category as $cat => $products): $cat_key = $ci++; ?>
            <tbody data-cat="<?php echo $cat_key; ?>">
              <tr class="pb-cat-row"><td colspan="4"><?php echo htmlspecialchars($cat); ?></td></tr>
              <?php foreach ($products as $p): $new = isset($prices[(int) $p['id']]) ? $prices[(int) $p['id']] : NULL; ?>
                <tr class="pb-item" data-name="<?php echo htmlspecialchars($p['product_name']); ?>" data-sku="<?php echo htmlspecialchars((string) $p['sku']); ?>" data-base="<?php echo (float) $p['price']; ?>">
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($p['product_name']); ?></div>
                    <?php if ($p['sku']): ?><div class="small text-muted"><?php echo htmlspecialchars($p['sku']); ?></div><?php endif; ?>
                  </td>
                  <td class="text-end text-nowrap"><?php echo $money($p['price']); ?></td>
                  <td class="pb-new-col">
                    <input type="text" inputmode="numeric" name="prices[<?php echo $p['id']; ?>]" class="form-control form-control-sm text-end pb-input"
                           placeholder="<?php echo $money($p['price']); ?>" value="<?php echo $new !== NULL ? $money($new) : ''; ?>">
                  </td>
                  <td class="text-end pb-diff-col"><span class="pb-diff"></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          <?php endforeach; ?>
        </table>
      </div>
      <div id="pbEmpty" class="text-muted text-center py-4 d-none">Không tìm thấy món nào.</div>
    </div>
  </section>

  <div class="pb-savebar">
    <a href="<?php echo site_url('me/price-books'); ?>" class="btn btn-outline-secondary">Huỷ</a>
    <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu bảng giá</button>
  </div>
  <?php echo form_close(); ?>
</div>

<script>
(function(){
  var fmt = function(n){ return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
  var parse = function(s){ s = String(s || '').replace(/[\s.,]/g, ''); return /^\d+$/.test(s) ? parseInt(s, 10) : null; };
  var fold = function(s){ return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[đĐ]/g, 'd').toLowerCase().trim(); };
  var rows = Array.prototype.slice.call(document.querySelectorAll('.pb-item'));

  // Chênh lệch so với giá gốc: −10% (xanh = giảm), +5% (cam = tăng).
  function refresh(tr){
    var base = parseFloat(tr.dataset.base), val = parse(tr.querySelector('.pb-input').value), el = tr.querySelector('.pb-diff');
    tr.classList.toggle('has-price', val !== null);
    if (val === null || val === base || ! base) { el.textContent = val === base ? 'bằng giá gốc' : ''; el.className = 'pb-diff'; return; }
    var pct = Math.round((val - base) / base * 100);
    el.textContent = (val > base ? '+' : '−') + fmt(Math.abs(val - base)) + ' (' + (pct > 0 ? '+' : '') + pct + '%)';
    el.className = 'pb-diff ' + (val < base ? 'down' : 'up');
  }
  function count(){ document.getElementById('pbCount').textContent = rows.filter(function(tr){ return tr.classList.contains('has-price'); }).length; }
  rows.forEach(function(tr){
    var input = tr.querySelector('.pb-input');
    input.addEventListener('input', function(){ refresh(tr); count(); });
    input.addEventListener('blur', function(){ var v = parse(input.value); input.value = v === null ? '' : fmt(v); });
    refresh(tr);
  });
  count();

  // Thứ áp dụng nhanh.
  document.querySelectorAll('[data-days]').forEach(function(b){
    b.addEventListener('click', function(){
      var days = b.dataset.days.split(',');
      document.querySelectorAll('input[name="weekdays[]"]').forEach(function(c){ c.checked = days.indexOf(c.value) !== -1; });
    });
  });

  // Điều chỉnh nhanh theo danh mục.
  function targets(){
    var cat = document.getElementById('bulkCat').value;
    return rows.filter(function(tr){ return cat === 'all' || tr.parentNode.dataset.cat === cat; });
  }
  document.getElementById('bulkApply').addEventListener('click', function(){
    var v = parseFloat(document.getElementById('bulkVal').value), dir = parseInt(document.getElementById('bulkDir').value, 10);
    var pct = document.getElementById('bulkUnit').value === 'pct';
    if ( ! (v > 0)) { document.getElementById('bulkVal').focus(); return; }
    targets().forEach(function(tr){
      var base = parseFloat(tr.dataset.base);
      var price = pct ? Math.round(base * (1 + dir * v / 100) / 1000) * 1000 : base + dir * v;
      tr.querySelector('.pb-input').value = fmt(Math.max(0, price));
      refresh(tr);
    });
    count();
  });
  document.getElementById('bulkClear').addEventListener('click', function(){
    targets().forEach(function(tr){ tr.querySelector('.pb-input').value = ''; refresh(tr); });
    count();
  });

  // Tìm món (không cần dấu, tên hoặc SKU).
  document.getElementById('pbSearch').addEventListener('input', function(){
    var words = fold(this.value).split(/\s+/).filter(Boolean), shown = 0;
    document.querySelectorAll('.pb-table tbody').forEach(function(tb){
      var any = false;
      tb.querySelectorAll('.pb-item').forEach(function(tr){
        var hay = fold(tr.dataset.name) + ' ' + tr.dataset.sku.toLowerCase();
        var ok = words.every(function(w){ return hay.indexOf(w) !== -1; });
        tr.classList.toggle('d-none', ! ok);
        if (ok) { any = true; shown++; }
      });
      tb.classList.toggle('d-none', ! any);
    });
    document.getElementById('pbEmpty').classList.toggle('d-none', shown > 0);
  });
})();
</script>
