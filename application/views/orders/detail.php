<div class="container-fluid py-3 py-md-4">
  <?php if ($is_active): ?>
    <?php // Đơn đang phục vụ: tên bàn (+ ghi chú bàn) nằm ngay sau 2 tab Bàn | Thực đơn; nút "Khác" đứng trước "Toàn màn hình". ?>
    <?php ob_start(); ?>
      <span class="fw-bold fs-5 text-nowrap">
        <?php if (empty($order['table_id'])): ?><i class="bi bi-bag-check text-brand"></i><?php endif; ?>
        <?php echo htmlspecialchars($table_label); ?>
      </span>
      <?php if ($order['table_id']): // Ghi chú cố định của bàn (giữ qua các lượt khách) — bấm ✏️ để sửa. ?>
        <span id="tableNoteWrap" class="small text-muted ms-2 text-truncate <?php echo empty($order['table_note']) ? 'd-none' : ''; ?>">
          <i class="bi bi-sticky"></i> <span id="tableNoteText"><?php echo htmlspecialchars((string) $order['table_note']); ?></span>
          <a href="#" class="ms-1" onclick="editTableNote(); return false;"><i class="bi bi-pencil"></i></a>
        </span>
      <?php endif; ?>
    <?php $pos_tab_title = ob_get_clean(); ?>
    <?php ob_start(); ?>
      <?php if ($order['table_id']): ?>
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i> Khác</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?php echo site_url('me/tables/'.$order['table_id'].'/transfer'); ?>"><i class="bi bi-arrow-left-right"></i> Chuyển bàn</a></li>
          <li><a class="dropdown-item" href="<?php echo site_url('me/tables/'.$order['table_id'].'/merge'); ?>"><i class="bi bi-union"></i> Gộp bàn</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" onclick="editTableNote(); return false;"><i class="bi bi-sticky"></i> Ghi chú bàn</a></li>
        </ul>
      </div>
      <?php endif; ?>
    <?php $pos_tab_actions = ob_get_clean(); ?>
    <?php $this->load->view('orders/_pos_tabs', array('pos_tab_title' => $pos_tab_title, 'pos_tab_actions' => $pos_tab_actions)); ?>
  <?php else: ?>
    <a href="<?php echo site_url('me/orders'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Đơn hàng</a>

    <?php // Xem lại đơn đã đóng: tiêu đề riêng có mã đơn + trạng thái. ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div>
        <h4 class="fw-bold mb-0">
          <?php if (empty($order['table_id'])): ?><i class="bi bi-bag-check text-brand"></i><?php endif; ?>
          <?php echo htmlspecialchars($table_label); ?>
          <span class="text-muted fs-6">#<?php echo htmlspecialchars($order['order_no']); ?></span>
        </h4>
        <span class="badge bg-<?php echo order_status_badge($order['status']); ?>"><?php echo $order['status']; ?></span>
        <?php if ( ! empty($order['table_note'])): ?>
          <div class="small text-muted mt-1"><i class="bi bi-sticky"></i> <?php echo htmlspecialchars($order['table_note']); ?></div>
        <?php endif; ?>
      </div>
      <?php if ($order['status'] === 'PAID'): ?>
        <a href="<?php echo site_url('me/orders/'.$order['id'].'/invoice'); ?>" target="_blank" class="btn btn-sm btn-brand"><i class="bi bi-printer"></i> In hóa đơn</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div id="ajaxError" class="alert alert-danger py-2 small d-none"></div>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo $this->session->flashdata('error'); ?></div>
  <?php endif; ?>
  <?php if ($kitchen_slip): ?>
    <div class="alert alert-success py-2 small no-print"><i class="bi bi-check-circle"></i> Đã báo bếp. Đang mở hộp thoại in phiếu bếp…
      <a href="#" onclick="printSlip('kitchen'); return false;">In lại</a></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="<?php echo $is_active ? 'col-lg-5 order-lg-2' : 'col-lg-7'; ?>">
      <div id="orderPanel">
        <?php $this->load->view('orders/_order_panel'); ?>
      </div>

      <?php if ($is_active): ?>
      <div class="row g-2 pos-actions">
        <div class="col-4">
          <?php echo form_open('me/orders/'.$order['id'].'/notify', array('id' => 'notifyForm')); ?>
            <button type="submit" class="btn btn-warning btn-lg w-100 h-100" onclick="return waitIdle(this);">
              <i class="bi bi-megaphone"></i><div class="small">Thông báo</div>
            </button>
          <?php echo form_close(); ?>
        </div>
        <div class="col-4">
          <button type="button" class="btn btn-outline-dark btn-lg w-100 h-100" onclick="printProvisional();">
            <i class="bi bi-printer"></i><div class="small">In tạm tính</div>
          </button>
        </div>
        <div class="col-4">
          <button type="button" class="btn btn-brand btn-lg w-100 h-100" onclick="openPayModal();">
            <i class="bi bi-cash-coin"></i><div class="small">Thanh toán</div>
          </button>
        </div>
      </div>
      <div class="form-text mt-2">
        "Thông báo" gửi món mới/đổi cho bếp và in phiếu bếp; "In tạm tính" in phiếu cho khách xem — cả hai chưa kết thúc đơn.
      </div>
      <?php endif; ?>
    </div>

    <?php if ($is_active): ?>
    <div class="col-lg-7 order-lg-1">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white pb-0">
          <?php // Lọc theo danh mục ngay trên trang (không tải lại) — mặc định "Tất cả". 1 hàng trượt ngang như dãy chip đơn. ?>
          <div class="pos-order-chips-bar" id="categoryFilterBar">
            <button type="button" class="pos-chips-nav pos-chips-prev" aria-label="Danh mục trước"><i class="bi bi-chevron-left"></i></button>
            <div class="swiper pos-order-chips" id="categoryFilter">
              <div class="swiper-wrapper">
                <div class="swiper-slide"><button type="button" class="btn btn-sm btn-brand" data-cat="all" onclick="filterCategory('all', this)">Tất cả</button></div>
                <?php $cat_index = 0; foreach (array_keys($products_by_category) as $cat_name): ?>
                  <div class="swiper-slide"><button type="button" class="btn btn-sm btn-outline-brand" data-cat="<?php echo $cat_index++; ?>" onclick="filterCategory(this.dataset.cat, this)"><?php echo htmlspecialchars($cat_name); ?></button></div>
                <?php endforeach; ?>
              </div>
              <div class="swiper-scrollbar pos-chips-scrollbar"></div>
            </div>
            <button type="button" class="pos-chips-nav pos-chips-next" aria-label="Danh mục sau"><i class="bi bi-chevron-right"></i></button>
          </div>
          <script>posChipSwiper(document.getElementById('categoryFilterBar'), 0);</script>
        </div>
        <div class="card-body" style="max-height:70vh; overflow-y:auto;">
          <?php if (empty($products_by_category)): ?>
            <div class="text-muted text-center py-4">Chưa có sản phẩm nào đang bán.</div>
          <?php endif; ?>
          <?php // Bấm vào món = thêm ngay 1 phần vào "Món đã gọi"; bấm tiếp để tăng số lượng. ?>
          <div class="row g-2" id="productGrid">
            <?php $cat_index = 0; foreach ($products_by_category as $cat_name => $products): $cat_key = $cat_index++; ?>
              <?php foreach ($products as $p): ?>
              <div class="col-4 col-sm-2 col-xl-2 menu-product" data-cat="<?php echo $cat_key; ?>">
                <button type="button" class="pos-product-card w-100" onclick="addProduct(<?php echo $p['id']; ?>, this)">
                  <?php // Giá nằm đè giữa đáy ảnh để thẻ gọn hơn; tên món bên dưới. ?>
                  <div class="pos-product-media">
                    <?php if ($p['image']): ?>
                      <img src="<?php echo base_url('assets/'.$p['image']); ?>" alt="" class="pos-product-img">
                    <?php else: ?>
                      <div class="pos-product-img pos-product-img-empty"><i class="bi bi-cup-straw"></i></div>
                    <?php endif; ?>
                    <span class="pos-product-price"><?php echo money_format_vnd($p['price']); ?></span>
                  </div>
                  <div class="pos-product-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
                </button>
              </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($is_active): ?>
<!-- Thanh toán -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <?php echo form_open('me/orders/'.$order['id'].'/pay'); ?>
      <div class="modal-header">
        <h5 class="modal-title">Thanh toán — <?php echo htmlspecialchars($table_label); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning py-2 small <?php echo $pending_count ? '' : 'd-none'; ?>" id="payPendingWarn">
          <i class="bi bi-exclamation-triangle"></i> Còn <span id="payPendingCount"><?php echo $pending_count; ?></span> món chưa báo bếp.
        </div>
        <div class="d-flex justify-content-between fs-4 fw-bold mb-3"><span>Tổng cộng</span><span class="text-brand" id="payTotal"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
        <div class="mb-3">
          <label class="form-label">Phương thức</label>
          <select name="payment_method" id="paymentMethod" class="form-select form-select-lg" onchange="onPayMethodChange()">
            <option value="CASH">Tiền mặt</option>
            <option value="CARD">Thẻ</option>
            <option value="TRANSFER">Chuyển khoản</option>
            <option value="QR">QR Pay</option>
          </select>
        </div>
        <div id="receivedGroup">
          <div class="mb-2">
            <label class="form-label">Khách đưa</label>
            <input type="number" name="received_amount" id="receivedAmount" class="form-control form-control-lg" min="0" step="1000"
                   value="<?php echo (int) $order['total_amount']; ?>" oninput="calcChange()">
          </div>
          <div class="fs-5">Tiền thối lại: <span class="fw-bold text-success" id="changeAmount">0đ</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-brand btn-lg w-100"><i class="bi bi-check2-circle"></i> Xác nhận thanh toán</button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<!-- Phiếu in (ẩn trên màn hình, chỉ hiện khi in) -->
<div id="provisionalSlipWrap"><?php $this->load->view('orders/_provisional_slip'); ?></div>

<?php if ($kitchen_slip): ?>
<div class="print-slip receipt-k80" data-slip="kitchen">
  <div class="center bold big">PHIẾU BẾP</div>
  <hr>
  <div class="bold"><?php echo empty($order['table_id']) ? 'MANG ĐI' : 'Bàn: '.htmlspecialchars($table_label); ?></div>
  <div>Mã đơn: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div>Giờ: <?php echo date('d/m/Y H:i', strtotime($kitchen_slip['created_at'])); ?> — <?php echo htmlspecialchars($kitchen_slip['staff']); ?></div>
  <?php if ( ! empty($kitchen_slip['order_note'])): ?>
    <div class="bold">Ghi chú: <?php echo htmlspecialchars($kitchen_slip['order_note']); ?></div>
  <?php endif; ?>
  <hr>
  <table>
    <?php foreach ($kitchen_slip['send'] as $line): ?>
    <tr><td><span class="bold"><?php echo (int) $line['qty']; ?> x</span> <?php echo htmlspecialchars($line['product_name']); ?></td></tr>
    <?php if ($line['note']): ?><tr><td>&nbsp;&nbsp;↳ <?php echo htmlspecialchars($line['note']); ?></td></tr><?php endif; ?>
    <?php endforeach; ?>
  </table>
  <?php if ( ! empty($kitchen_slip['changed'])): ?>
    <hr>
    <div class="bold">ĐỔI GHI CHÚ</div>
    <table>
      <?php foreach ($kitchen_slip['changed'] as $line): ?>
      <tr><td><span class="bold"><?php echo (int) $line['qty']; ?> x</span> <?php echo htmlspecialchars($line['product_name']); ?></td></tr>
      <tr><td>&nbsp;&nbsp;↳ <?php echo $line['note'] !== NULL && $line['note'] !== '' ? htmlspecialchars($line['note']) : '(bỏ ghi chú)'; ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <?php if ($kitchen_slip['cancel']): ?>
    <hr>
    <div class="bold">HỦY MÓN</div>
    <table>
      <?php foreach ($kitchen_slip['cancel'] as $line): ?>
      <tr><td class="strike"><span class="bold"><?php echo (int) $line['qty']; ?> x</span> <?php echo htmlspecialchars($line['product_name']); ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
<?php if ($is_active): ?>
// ---- Thêm/đổi số lượng/hủy món qua AJAX — server render lại khối "Món đã gọi" ----
var ORDER_URL = '<?php echo base_url('me/orders/'.$order['id']); ?>';
var CSRF_NAME = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CSRF_HASH = '<?php echo $this->security->get_csrf_hash(); ?>';
var ORDER_TOTAL = <?php echo (float) $order['total_amount']; ?>;
var pending = Promise.resolve();   // xếp hàng các request để bấm nhanh liên tiếp không bị lệch số lượng
var inFlight = 0;

function postOrder(path, params){
  var body = new URLSearchParams(params || {});
  body.append(CSRF_NAME, CSRF_HASH);
  inFlight++;
  pending = pending.then(function(){
    return fetch(ORDER_URL + path + '.html', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
      body: body
    })
    .then(function(r){ return r.json(); })
    .then(applyPanel)
    .catch(function(){ showAjaxError('Mất kết nối, vui lòng thử lại.'); })
    .then(function(){ inFlight--; });
  });
  return pending;
}

function applyPanel(res){
  if (res.panel_html !== undefined){
    document.getElementById('orderPanel').innerHTML = res.panel_html;
    document.getElementById('provisionalSlipWrap').innerHTML = res.slip_html;
    ORDER_TOTAL = res.total;
    document.getElementById('payTotal').textContent = res.total_text;
    document.getElementById('receivedAmount').value = Math.round(res.total);
    document.getElementById('payPendingCount').textContent = res.pending_count;
    document.getElementById('payPendingWarn').classList.toggle('d-none', ! res.pending_count);
  }
  showAjaxError(res.success === false ? (res.message || 'Không thực hiện được.') : null);
}

function showAjaxError(msg){
  var el = document.getElementById('ajaxError');
  el.textContent = msg || '';
  el.classList.toggle('d-none', ! msg);
}

function addProduct(pid, btn){
  btn.classList.remove('pos-product-added'); void btn.offsetWidth; btn.classList.add('pos-product-added');
  postOrder('/add-item', {'product_id[]': pid, 'qty[]': 1, 'note[]': ''});
}

function changeItemQty(itemId, qty){
  postOrder('/update-item/' + itemId, {qty: qty});
}

function removeItem(itemId){
  posConfirm('Hủy món này?', 'Hủy món').then(function(ok){
    if (ok) postOrder('/cancel-item/' + itemId);
  });
}

// ---- Ghi chú (hộp nhập trong trang, không mất toàn màn hình) ----
var ITEM_NOTE_SUGGESTIONS = ['Ít đá', 'Không đá', 'Ít đường', 'Không đường', 'Nhiều sữa', 'Ít sữa', 'Nóng', 'Mang về'];

function editItemNote(btn){
  posPrompt('Ghi chú món', btn.dataset.note, ITEM_NOTE_SUGGESTIONS).then(function(note){
    if (note !== null && note !== btn.dataset.note) postOrder('/item-note/' + btn.dataset.id, {note: note});
  });
}

function editOrderNote(btn){
  posPrompt('Ghi chú cho cả đơn', btn.dataset.note, []).then(function(note){
    if (note !== null && note !== btn.dataset.note) postOrder('/note', {note: note});
  });
}

<?php if ($order['table_id']): ?>
var TABLE_NOTE_URL = '<?php echo base_url('me/tables/'.$order['table_id'].'/note.html'); ?>';
function editTableNote(){
  var current = document.getElementById('tableNoteText').textContent;
  posPrompt('Ghi chú bàn <?php echo htmlspecialchars($table_label, ENT_QUOTES); ?> (giữ qua các lượt khách)', current, []).then(function(note){
    if (note === null || note === current) return;
    var body = new URLSearchParams({note: note});
    body.append(CSRF_NAME, CSRF_HASH);
    fetch(TABLE_NOTE_URL, {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'}, body: body})
      .then(function(r){ return r.json(); })
      .then(function(res){
        if ( ! res.success){ showAjaxError(res.message || 'Không lưu được ghi chú bàn.'); return; }
        document.getElementById('tableNoteText').textContent = res.note || '';
        document.getElementById('tableNoteWrap').classList.toggle('d-none', ! res.note);
        showAjaxError(null);
      })
      .catch(function(){ showAjaxError('Mất kết nối, vui lòng thử lại.'); });
  });
}
<?php endif; ?>

// Chờ các thao tác thêm/đổi món xong rồi mới chạy (Thông báo / In tạm tính / Thanh toán).
function whenIdle(fn){ pending.then(fn); }

function waitIdle(btn){
  if (inFlight === 0) return true;
  whenIdle(function(){ btn.form.submit(); });
  return false;
}

// ---- Lọc thực đơn theo danh mục ----
function filterCategory(cat, btn){
  document.querySelectorAll('.menu-product').forEach(function(el){
    el.classList.toggle('d-none', cat !== 'all' && el.dataset.cat !== cat);
  });
  document.querySelectorAll('#categoryFilter button').forEach(function(b){
    b.classList.toggle('btn-brand', b === btn);
    b.classList.toggle('btn-outline-brand', b !== btn);
  });
}

// ---- In phiếu ngay trên trang (khổ K80) ----
function printSlip(name){ posPrintSlip(name); }

function printProvisional(){
  whenIdle(function(){ printSlip('provisional'); });
}

// ---- Thanh toán ----
function openPayModal(){
  whenIdle(function(){
    onPayMethodChange();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('payModal')).show();
  });
}

function onPayMethodChange(){
  var isCash = document.getElementById('paymentMethod').value === 'CASH';
  document.getElementById('receivedGroup').classList.toggle('d-none', !isCash);
  calcChange();
}

function calcChange(){
  var received = parseFloat(document.getElementById('receivedAmount').value) || 0;
  document.getElementById('changeAmount').textContent = Math.max(0, received - ORDER_TOTAL).toLocaleString('vi-VN') + 'đ';
}

<?php if ($kitchen_slip): ?>
window.addEventListener('load', function(){ printSlip('kitchen'); });
<?php endif; ?>
<?php endif; ?>
</script>
