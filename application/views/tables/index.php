<div class="container-fluid py-3 py-md-4">
  <?php
    // Tiêu đề "Sơ đồ bàn" ngay sau 2 tab; trước "Toàn màn hình" là 2 nút Bảng giá / Khuyến mãi (giống ứng dụng POS):
    // chọn ở đây áp ngay cho MỌI đơn đang mở (chưa thanh toán) và bàn mở sau; trang đơn chỉ bỏ được cho riêng đơn đó.
    // Nút tô màu khi đang áp; ẩn khi hôm nay không có gì để chọn. (Quản lý bàn: ở trang Quản trị.)
    $cur_book = $shop_offers['price_book'];
    $cur_disc = $shop_offers['discount'];
    $offer_menu = function ($kind, $icon, $title, $current, $none_label, $empty_label, $options, $selected_id) {
      $on = $current !== NULL;
  ?>
    <div class="dropdown shop-offer" data-kind="<?php echo $kind; ?>">
      <button type="button" class="btn btn-sm shop-offer-btn <?php echo $on ? 'is-on' : ''; ?>" data-bs-toggle="dropdown" data-bs-auto-close="true"
              aria-expanded="false" title="<?php echo htmlspecialchars($title.': '.($on ? $current : $none_label)); ?>">
        <i class="bi <?php echo $icon; ?>"></i>
        <span class="shop-offer-label"><?php echo htmlspecialchars($on ? $current : $title); ?></span>
        <span class="shop-offer-dot"></span>
      </button>
      <div class="dropdown-menu dropdown-menu-end shop-offer-menu">
        <div class="shop-offer-head"><?php echo mb_strtoupper($title, 'UTF-8'); ?></div>
        <button type="button" class="dropdown-item shop-offer-opt <?php echo ! $on ? 'is-selected' : ''; ?>" data-id="0" <?php echo $options || $on ? '' : 'disabled'; ?>>
          <i class="bi bi-check2"></i><span><b><?php echo $options || $on ? $none_label : $empty_label; ?></b></span>
        </button>
        <?php foreach ($options as $o): ?>
          <button type="button" class="dropdown-item shop-offer-opt <?php echo (int) $selected_id === (int) $o[0] ? 'is-selected' : ''; ?>" data-id="<?php echo (int) $o[0]; ?>">
            <i class="bi bi-check2"></i><span><b><?php echo htmlspecialchars($o[1]); ?></b><?php if ($o[2] !== NULL): ?><small><?php echo htmlspecialchars($o[2]); ?></small><?php endif; ?></span>
          </button>
        <?php endforeach; ?>
        <div class="dropdown-divider"></div>
        <div class="shop-offer-note">Áp dụng cho mọi bàn đang phục vụ (chưa thanh toán) và bàn mở sau. Trang đơn có thể bỏ cho từng đơn.</div>
      </div>
    </div>
  <?php
    };
  ?>
  <?php ob_start(); ?>
    <span class="fw-bold fs-5 text-nowrap">Sơ đồ bàn</span>
  <?php $pos_tab_title = ob_get_clean(); ?>
  <?php ob_start(); ?>
    <?php if ($price_books || $discounts || $cur_book || $cur_disc): ?>
      <?php $offer_menu('book', 'bi-tags', 'Bảng giá', $cur_book ? $cur_book['name'] : NULL, 'Giá gốc', 'Không có bảng giá hôm nay',
              array_map(function ($b) { return array($b['id'], $b['name'], NULL); }, $price_books), $cur_book ? $cur_book['id'] : 0); ?>
      <?php $offer_menu('discount', 'bi-percent', 'Khuyến mãi', $cur_disc ? $cur_disc['name'] : NULL, 'Không áp dụng', 'Không có khuyến mãi hôm nay',
              array_map(function ($d) { return array($d['id'], $d['name'], Discount_model::describe($d['discount_type'], $d['value'], $d['max_discount'], $d['min_order'])); }, $discounts),
              $cur_disc ? $cur_disc['id'] : 0); ?>
    <?php endif; ?>
  <?php $pos_tab_actions = ob_get_clean(); ?>
  <?php $this->load->view('orders/_pos_tabs', array('pos_tab_title' => $pos_tab_title, 'pos_tab_actions' => $pos_tab_actions)); ?>

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

// Bảng giá / Khuyến mãi (nút trên thanh tiêu đề): chọn -> áp ngay cho mọi đơn đang mở (server tính lại),
// cập nhật nút + dấu chọn tại chỗ và làm mới tiền trên các bàn.
(function(){
  var menus = document.querySelectorAll('.shop-offer');
  if ( ! menus.length) return;
  var state = {
    book: <?php echo $cur_book ? (int) $cur_book['id'] : 0; ?>,
    discount: <?php echo $cur_disc ? (int) $cur_disc['id'] : 0; ?>
  };
  var busy = false;
  function render(){
    menus.forEach(function(m){
      var id = state[m.dataset.kind], btn = m.querySelector('.shop-offer-btn'), title = m.dataset.kind === 'book' ? 'Bảng giá' : 'Khuyến mãi';
      var sel = null;
      m.querySelectorAll('.shop-offer-opt').forEach(function(o){
        var on = parseInt(o.dataset.id, 10) === id;
        o.classList.toggle('is-selected', on);
        if (on) sel = o;
      });
      btn.classList.toggle('is-on', id !== 0);
      var name = id !== 0 && sel ? sel.querySelector('b').textContent : null;
      m.querySelector('.shop-offer-label').textContent = name || title;
      btn.title = title + ': ' + (name || (m.dataset.kind === 'book' ? 'Giá gốc' : 'Không áp dụng'));
    });
  }
  function toast(text, ok){
    var t = document.getElementById('offerToast');
    if ( ! t) { t = document.createElement('div'); t.id = 'offerToast'; document.body.appendChild(t); }
    t.className = 'shop-offer-toast ' + (ok ? 'ok' : 'err');
    t.innerHTML = '<i class="bi ' + (ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + '"></i> ';
    t.appendChild(document.createTextNode(text));
    clearTimeout(t._h); t._h = setTimeout(function(){ t.remove(); }, 3500);
  }
  document.querySelectorAll('.shop-offer-opt').forEach(function(opt){
    opt.addEventListener('click', function(){
      var kind = opt.closest('.shop-offer').dataset.kind, id = parseInt(opt.dataset.id, 10);
      if (busy || id === state[kind]) return;
      var next = {book: state.book, discount: state.discount};
      next[kind] = id;
      var body = new URLSearchParams({price_book_id: next.book, discount_id: next.discount});
      body.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
      busy = true;
      menus.forEach(function(m){ m.querySelector('.shop-offer-btn').classList.add('is-busy'); });
      fetch('<?php echo base_url('me/tables/offers.html'); ?>', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'}, body: body})
        .then(function(r){ return r.json(); })
        .then(function(res){
          if ( ! res.success) throw new Error(res.message);
          state = next;
          render();
          toast(res.message, true);
          refreshTables();
        })
        .catch(function(e){ toast(e.message || 'Mất kết nối, vui lòng thử lại.', false); })
        .then(function(){ busy = false; menus.forEach(function(m){ m.querySelector('.shop-offer-btn').classList.remove('is-busy'); }); });
    });
  });
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
