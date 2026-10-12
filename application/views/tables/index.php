<div class="container-fluid py-3 py-md-4">
  <?php // Tiêu đề "Sơ đồ bàn" nằm ngay sau 2 tab; nút "Quản lý bàn" (ADMIN) đứng trước "Toàn màn hình". ?>
  <?php ob_start(); ?>
    <span class="fw-bold fs-5 text-nowrap">Sơ đồ bàn</span>
  <?php $pos_tab_title = ob_get_clean(); ?>
  <?php ob_start(); ?>
    <?php if ($current_user['role'] === 'ADMIN'): ?>
      <a href="<?php echo site_url('me/tables/manage'); ?>" class="btn btn-sm btn-outline-secondary text-nowrap"><i class="bi bi-sliders"></i> Quản lý bàn</a>
    <?php endif; ?>
  <?php $pos_tab_actions = ob_get_clean(); ?>
  <?php $this->load->view('orders/_pos_tabs', array('pos_tab_title' => $pos_tab_title, 'pos_tab_actions' => $pos_tab_actions)); ?>

  <?php
    // Bảng giá + khuyến mãi: chọn ở đây, áp ngay cho MỌI đơn đang mở (chưa thanh toán) và bàn mở sau.
    // Trang đơn chỉ bỏ được cho riêng đơn đó. Chỉ hiện khi hôm nay có bảng giá / khuyến mãi hoặc đang chọn.
    $cur_book = $shop_offers['price_book'];
    $cur_disc = $shop_offers['discount'];
  ?>
  <?php if ($price_books || $discounts || $cur_book || $cur_disc): ?>
  <div class="pos-offers pos-offers-shop mb-3" id="shopOffers">
    <div class="pos-offer pos-price-book <?php echo $cur_book ? 'is-on' : ''; ?>">
      <label for="shopPriceBook"><i class="bi bi-tags"></i> Bảng giá</label>
      <select id="shopPriceBook" class="form-select" <?php echo $price_books ? '' : 'disabled'; ?>>
        <option value="0"><?php echo $price_books ? 'Giá gốc' : 'Không có bảng giá hôm nay'; ?></option>
        <?php foreach ($price_books as $pb): ?>
          <option value="<?php echo (int) $pb['id']; ?>" <?php echo $cur_book && (int) $cur_book['id'] === (int) $pb['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pb['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="pos-offer pos-discount <?php echo $cur_disc ? 'is-on' : ''; ?>">
      <label for="shopDiscount"><i class="bi bi-percent"></i> Khuyến mãi</label>
      <select id="shopDiscount" class="form-select" <?php echo $discounts ? '' : 'disabled'; ?>>
        <option value="0"><?php echo $discounts ? 'Không áp dụng' : 'Không có khuyến mãi hôm nay'; ?></option>
        <?php foreach ($discounts as $dc): ?>
          <option value="<?php echo (int) $dc['id']; ?>" <?php echo $cur_disc && (int) $cur_disc['id'] === (int) $dc['id'] ? 'selected' : ''; ?>
                  title="<?php echo htmlspecialchars(Discount_model::describe($dc['discount_type'], $dc['value'], $dc['max_discount'], $dc['min_order'])); ?>"><?php echo htmlspecialchars($dc['name']); ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($cur_disc): ?><div class="pos-offer-hint"><?php echo htmlspecialchars(Discount_model::describe($cur_disc['discount_type'], $cur_disc['value'], $cur_disc['max_discount'], $cur_disc['min_order'])); ?></div><?php endif; ?>
    </div>
    <div class="pos-offers-note"><i class="bi bi-info-circle"></i> Áp dụng cho mọi bàn đang phục vụ (chưa thanh toán) và bàn mở sau. Trang đơn có thể bỏ cho từng đơn.</div>
  </div>
  <?php endif; ?>

  <?php if ($paid_order_id): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center py-2">
      <span><i class="bi bi-check-circle"></i> Đã thanh toán — bàn đã về trống.</span>
      <?php if ($paid_invoice): ?>
        <button type="button" class="btn btn-sm btn-success" onclick="posPrintSlip('invoice')"><i class="bi bi-printer"></i> In lại hóa đơn</button>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php
    $render_table_card = function ($t) {
  ?>
    <div class="col-6 col-sm-4 col-md-3 col-lg-2" data-table-id="<?php echo $t['id']; ?>">
      <?php if ($t['status'] === 'AVAILABLE'): ?>
        <a href="<?php echo site_url('me/tables/'.$t['id'].'/open'); ?>" class="text-decoration-none table-link">
      <?php else: ?>
        <a href="<?php echo site_url('me/tables/'.$t['id']); ?>" class="text-decoration-none table-link">
      <?php endif; ?>
        <div class="card table-card border-0 h-100 position-relative">
          <div class="card-body text-center">
            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($t['table_name']); ?></div>
            <?php if ($t['is_takeaway']): ?>
            <div class="small text-muted mb-2"><i class="bi bi-bag-check"></i> Bán mang đi</div>
            <?php else: ?>
            <div class="small text-muted mb-2"><i class="bi bi-people"></i> <?php echo $t['capacity']; ?> chỗ</div>
            <?php endif; ?>
            <span class="badge bg-<?php echo table_status_badge($t['status']); ?> table-status-badge">
              <?php echo array('AVAILABLE'=>'Trống','OPEN'=>'Đang phục vụ','WAIT_PAYMENT'=>'Chờ TT','PAID'=>'Đã TT')[$t['status']]; ?>
            </span>
            <?php if ( ! empty($t['note'])): ?>
              <div class="small text-muted text-truncate mt-1" title="<?php echo htmlspecialchars($t['note']); ?>"><i class="bi bi-sticky"></i> <?php echo htmlspecialchars($t['note']); ?></div>
            <?php endif; ?>
            <div class="mt-2 fw-semibold text-danger table-amount"><?php echo ! empty($t['order']) ? money_format_vnd($t['order']['total_amount']) : ''; ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php
    };
  ?>

  <div class="row g-3 mb-4" id="tablesGrid">
    <?php foreach ($tables as $t) $render_table_card($t); ?>
  </div>
</div>

<script>
var STATUS_LABEL = {AVAILABLE:'Trống', OPEN:'Đang phục vụ', WAIT_PAYMENT:'Chờ TT', PAID:'Đã TT'};
var STATUS_COLOR = {AVAILABLE:'success', OPEN:'primary', WAIT_PAYMENT:'warning', PAID:'info'};

function refreshTables(){
  fetch('<?php echo base_url('api/tables/status'); ?>')
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res.success) return;
      res.tables.forEach(function(t){
        var col = document.querySelector('[data-table-id="'+t.id+'"]');
        if (!col) return;
        var badge = col.querySelector('.table-status-badge');
        badge.className = 'badge bg-'+STATUS_COLOR[t.status]+' table-status-badge';
        badge.textContent = STATUS_LABEL[t.status];
        col.querySelector('.table-amount').textContent = t.total_amount ? Number(t.total_amount).toLocaleString('vi-VN')+'đ' : '';
        var link = col.querySelector('.table-link');
        link.setAttribute('href', t.status === 'AVAILABLE' ? '<?php echo base_url('me/tables'); ?>/'+t.id+'/open.html' : '<?php echo base_url('me/tables'); ?>/'+t.id+'.html');
      });
    })
    .catch(function(){});
}
setInterval(refreshTables, 5000);

// Chọn bảng giá / khuyến mãi -> áp ngay cho mọi đơn đang mở (server tính lại), làm mới tiền trên các bàn.
(function(){
  var bar = document.getElementById('shopOffers');
  if ( ! bar) return;
  var book = document.getElementById('shopPriceBook'), disc = document.getElementById('shopDiscount');
  var saved = {book: book.value, disc: disc.value};
  function apply(){
    var body = new URLSearchParams({price_book_id: book.value, discount_id: disc.value});
    body.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
    book.disabled = disc.disabled = true;
    fetch('<?php echo base_url('me/tables/offers.html'); ?>', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'}, body: body})
      .then(function(r){ return r.json(); })
      .then(function(res){
        if ( ! res.success) throw new Error(res.message);
        saved = {book: book.value, disc: disc.value};
        book.closest('.pos-offer').classList.toggle('is-on', book.value !== '0');
        disc.closest('.pos-offer').classList.toggle('is-on', disc.value !== '0');
        var hint = disc.closest('.pos-offer').querySelector('.pos-offer-hint');
        if (hint) hint.remove();
        showOfferToast(res.message, true);
        refreshTables();
      })
      .catch(function(e){ book.value = saved.book; disc.value = saved.disc; showOfferToast(e.message || 'Mất kết nối, vui lòng thử lại.', false); })
      .then(function(){ book.disabled = book.options.length < 2; disc.disabled = disc.options.length < 2; });
  }
  function showOfferToast(text, ok){
    var t = document.getElementById('offerToast');
    if ( ! t) { t = document.createElement('div'); t.id = 'offerToast'; bar.appendChild(t); }
    t.className = 'pos-offers-toast ' + (ok ? 'ok' : 'err');
    t.innerHTML = '<i class="bi ' + (ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + '"></i> ';
    t.appendChild(document.createTextNode(text));
    clearTimeout(t._h); t._h = setTimeout(function(){ t.remove(); }, 3500);
  }
  book.addEventListener('change', apply);
  disc.addEventListener('change', apply);
})();
</script>

<?php if ($paid_invoice): ?>
<!-- Hóa đơn của đơn vừa thanh toán — ẩn trên màn hình, tự mở hộp thoại in khi trang tải xong. -->
<div class="print-slip receipt-k80" data-slip="invoice">
  <?php $this->load->view('orders/_invoice_body', $paid_invoice); ?>
</div>
<script>
window.addEventListener('load', function(){ posPrintSlip('invoice'); });
</script>
<?php endif; ?>
