<?php
  // Trạng thái pha chế theo từng sản phẩm (ưu tiên NEW > PREPARING > COMPLETED trong
  // mọi ticket của đơn này) — dùng để tô viền ảnh món trong danh sách "Món đã gọi".
  $kitchen_status_by_product = array();
  $kitchen_poll_active = ($tickets && $is_active);
  if ($kitchen_poll_active)
  {
      $rank = array('NEW' => 3, 'PREPARING' => 2, 'COMPLETED' => 1);
      foreach ($tickets as $t)
      {
          foreach ($t['items'] as $ti)
          {
              $pid = $ti['product_id'];
              if ( ! isset($kitchen_status_by_product[$pid]) || $rank[$ti['status']] > $rank[$kitchen_status_by_product[$pid]])
              {
                  $kitchen_status_by_product[$pid] = $ti['status'];
              }
          }
      }
  }
  $table_label = $order['table_name'] ?: 'Mang đi';
  $active_items = array_filter($items, function ($it) { return $it['status'] === 'ACTIVE'; });
?>
<div class="container-fluid py-3 py-md-4">
  <?php if ($is_active): ?>
    <?php $this->load->view('orders/_pos_tabs'); ?>
  <?php else: ?>
    <a href="<?php echo site_url('me/orders'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Đơn hàng</a>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">
        <?php if (empty($order['table_id'])): ?><i class="bi bi-bag-check text-brand"></i><?php endif; ?>
        <?php echo htmlspecialchars($table_label); ?>
        <span class="text-muted fs-6">#<?php echo htmlspecialchars($order['order_no']); ?></span>
      </h4>
      <span class="badge bg-<?php echo order_status_badge($order['status']); ?>"><?php echo $order['status']; ?></span>
    </div>
    <?php if ($is_active && $order['table_id']): ?>
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i> Khác</button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?php echo site_url('me/tables/'.$order['table_id'].'/transfer'); ?>"><i class="bi bi-arrow-left-right"></i> Chuyển bàn</a></li>
        <li><a class="dropdown-item" href="<?php echo site_url('me/tables/'.$order['table_id'].'/merge'); ?>"><i class="bi bi-union"></i> Gộp bàn</a></li>
      </ul>
    </div>
    <?php elseif ($order['status'] === 'PAID'): ?>
      <a href="<?php echo site_url('me/orders/'.$order['id'].'/invoice'); ?>" target="_blank" class="btn btn-sm btn-brand"><i class="bi bi-printer"></i> In hóa đơn</a>
    <?php endif; ?>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo $this->session->flashdata('error'); ?></div>
  <?php endif; ?>
  <?php if ($kitchen_slip): ?>
    <div class="alert alert-success py-2 small no-print"><i class="bi bi-check-circle"></i> Đã báo bếp. Đang mở hộp thoại in phiếu bếp…
      <a href="#" onclick="printSlip('kitchen'); return false;">In lại</a></div>
  <?php endif; ?>

  <div class="row g-3">
    <?php
      // Đơn đang phục vụ: món đã hủy không hiện trong danh sách (món đã báo bếp vẫn được giữ
      // ngầm để lần "Thông báo" sau in mục HỦY). Đơn đã đóng: hiện đủ để xem lại lịch sử.
      $visible_items = $is_active ? $active_items : $items;
    ?>
    <div class="<?php echo $is_active ? 'col-lg-5 order-lg-2' : 'col-lg-7'; ?>">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
          <span>Món đã gọi</span>
          <?php if ($is_active && $pending_count): ?><span class="badge bg-warning text-dark"><?php echo $pending_count; ?> món chưa báo bếp</span><?php endif; ?>
        </div>
        <div class="list-group list-group-flush" id="orderedItemsList">
          <?php foreach ($visible_items as $it): ?>
            <?php $this->load->view('orders/_item_row', array('it' => $it, 'order' => $order, 'is_active' => $is_active, 'kitchen_status' => isset($kitchen_status_by_product[$it['product_id']]) ? $kitchen_status_by_product[$it['product_id']] : NULL)); ?>
          <?php endforeach; ?>
          <?php if (empty($visible_items)): ?>
            <div class="list-group-item text-muted text-center py-4">Chưa có món nào — chọn món ở thực đơn.</div>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white">
          <div class="d-flex justify-content-between small"><span>Tạm tính</span><span><?php echo money_format_vnd($order['subtotal']); ?></span></div>
          <div class="d-flex justify-content-between small"><span>Giảm giá</span><span>-<?php echo money_format_vnd($order['discount_amount']); ?></span></div>
          <div class="d-flex justify-content-between small"><span>VAT</span><span><?php echo money_format_vnd($order['vat_amount']); ?></span></div>
          <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>Tổng cộng</span><span class="text-brand"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
        </div>
      </div>

      <?php if ($is_active): ?>
      <div class="row g-2 pos-actions">
        <div class="col-4">
          <?php echo form_open('me/orders/'.$order['id'].'/notify', array('id' => 'notifyForm')); ?>
            <div id="notifyInputs"></div>
            <button type="submit" class="btn btn-warning btn-lg w-100 h-100" onclick="return prepareNotify();">
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
        <div class="card-header bg-white">
          <div class="fw-semibold mb-2">Thực đơn</div>
          <?php // Lọc theo danh mục ngay trên trang (không tải lại) — mặc định "Tất cả". ?>
          <div class="d-flex flex-wrap gap-2" id="categoryFilter">
            <button type="button" class="btn btn-sm btn-brand" data-cat="all" onclick="filterCategory('all', this)">Tất cả</button>
            <?php $cat_index = 0; foreach (array_keys($products_by_category) as $cat_name): ?>
              <button type="button" class="btn btn-sm btn-outline-brand" data-cat="<?php echo $cat_index++; ?>" onclick="filterCategory(this.dataset.cat, this)"><?php echo htmlspecialchars($cat_name); ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card-body" style="max-height:65vh; overflow-y:auto;">
          <?php echo form_open('me/orders/'.$order['id'].'/add-item', array('id' => 'addItemForm')); ?>
          <?php if (empty($products_by_category)): ?>
            <div class="text-muted text-center py-4">Chưa có sản phẩm nào đang bán.</div>
          <?php endif; ?>
          <?php $cat_index = 0; foreach ($products_by_category as $cat_name => $products): $cat_key = $cat_index++; ?>
            <div class="fw-semibold text-brand mt-2 mb-1 menu-cat-heading" data-cat="<?php echo $cat_key; ?>"><?php echo htmlspecialchars($cat_name); ?></div>
            <?php foreach ($products as $p): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2 menu-product" data-cat="<?php echo $cat_key; ?>">
              <div class="d-flex align-items-center gap-2" role="button" onclick="stepAddItemQty(<?php echo $p['id']; ?>,1)">
                <?php if ($p['image']): ?>
                  <img src="<?php echo base_url('assets/'.$p['image']); ?>" style="width:40px;height:40px;object-fit:cover;" class="rounded border flex-shrink-0">
                <?php else: ?>
                  <div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted flex-shrink-0" style="width:40px;height:40px;"><i class="bi bi-cup-straw"></i></div>
                <?php endif; ?>
                <div>
                  <div><?php echo htmlspecialchars($p['product_name']); ?></div>
                  <div class="small text-muted"><?php echo money_format_vnd($p['price']); ?></div>
                </div>
              </div>
              <div class="qty-stepper">
                <button type="button" onclick="stepAddItemQty(<?php echo $p['id']; ?>,-1)"><i class="bi bi-dash-lg"></i></button>
                <span id="add-item-qty-<?php echo $p['id']; ?>">0</span>
                <button type="button" onclick="stepAddItemQty(<?php echo $p['id']; ?>,1)"><i class="bi bi-plus-lg"></i></button>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <div id="addItemInputs"></div>
          <?php echo form_close(); ?>
        </div>
        <div class="card-footer bg-white">
          <button type="submit" form="addItemForm" class="btn btn-outline-brand w-100" onclick="return fillCartInputs('addItemInputs', true);">
            <i class="bi bi-plus-circle"></i> Thêm vào đơn <span id="cartCount" class="badge bg-brand d-none">0</span>
          </button>
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
        <?php if ($pending_count): ?>
          <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle"></i> Còn <?php echo $pending_count; ?> món chưa báo bếp.</div>
        <?php endif; ?>
        <div class="d-flex justify-content-between fs-4 fw-bold mb-3"><span>Tổng cộng</span><span class="text-brand"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
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
<div class="print-slip receipt-k80" data-slip="provisional">
  <div class="center bold big">Leno</div>
  <div class="center">PHIẾU TẠM TÍNH</div>
  <hr>
  <div><?php echo empty($order['table_id']) ? 'Mang đi' : 'Bàn: '.htmlspecialchars($table_label); ?></div>
  <div>Mã đơn: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div>Thời gian: <span class="js-print-time"></span></div>
  <hr>
  <table>
    <?php foreach ($active_items as $it): ?>
    <tr><td colspan="2"><?php echo htmlspecialchars($it['product_name']); ?></td></tr>
    <tr>
      <td><?php echo $it['qty']; ?> x <?php echo number_format($it['price'], 0, ',', '.'); ?></td>
      <td class="right"><?php echo number_format($it['amount'], 0, ',', '.'); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <hr>
  <table>
    <tr><td>Tạm tính</td><td class="right"><?php echo number_format($order['subtotal'], 0, ',', '.'); ?></td></tr>
    <tr><td>Giảm giá</td><td class="right">-<?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></td></tr>
    <tr><td>VAT</td><td class="right"><?php echo number_format($order['vat_amount'], 0, ',', '.'); ?></td></tr>
    <tr class="bold big"><td>TỔNG CỘNG</td><td class="right"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td></tr>
  </table>
  <hr>
  <div class="center">-- Phiếu tạm tính, chưa phải hóa đơn --</div>
</div>

<?php if ($kitchen_slip): ?>
<div class="print-slip receipt-k80" data-slip="kitchen">
  <div class="center bold big">PHIẾU BẾP</div>
  <hr>
  <div class="bold"><?php echo empty($order['table_id']) ? 'MANG ĐI' : 'Bàn: '.htmlspecialchars($table_label); ?></div>
  <div>Mã đơn: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div>Giờ: <?php echo date('d/m/Y H:i', strtotime($kitchen_slip['created_at'])); ?> — <?php echo htmlspecialchars($kitchen_slip['staff']); ?></div>
  <hr>
  <table>
    <?php foreach ($kitchen_slip['send'] as $line): ?>
    <tr><td><span class="bold"><?php echo (int) $line['qty']; ?> x</span> <?php echo htmlspecialchars($line['product_name']); ?></td></tr>
    <?php if ($line['note']): ?><tr><td>&nbsp;&nbsp;↳ <?php echo htmlspecialchars($line['note']); ?></td></tr><?php endif; ?>
    <?php endforeach; ?>
  </table>
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
// ---- Viền ảnh món theo trạng thái pha chế mới nhất ----
var KITCHEN_RANK = {NEW: 3, PREPARING: 2, COMPLETED: 1};
var KITCHEN_BORDER_COLOR = {NEW: 'danger', PREPARING: 'warning', COMPLETED: 'success'};

function applyKitchenBorder(el, status){
  el.classList.remove('border-danger', 'border-warning', 'border-success', 'order-kitchen-flash');
  if ( ! status) return;
  el.classList.add('border-'+KITCHEN_BORDER_COLOR[status]);
  if (status !== 'COMPLETED') el.classList.add('order-kitchen-flash');
}

function refreshKitchenBorders(){
  fetch('<?php echo site_url("me/orders/".$order['id']."/ticket-status"); ?>')
    .then(function(r){ return r.json(); })
    .then(function(res){
      if ( ! res.success) return;
      var statusByProduct = {};
      res.tickets.forEach(function(t){
        t.items.forEach(function(it){
          var cur = statusByProduct[it.product_id];
          if ( ! cur || KITCHEN_RANK[it.status] > KITCHEN_RANK[cur]) statusByProduct[it.product_id] = it.status;
        });
      });
      document.querySelectorAll('#orderedItemsList [data-product-id]').forEach(function(row){
        if (row.classList.contains('opacity-50')) return; // món đã hủy — không tô viền
        var img = row.querySelector('.item-kitchen-img');
        if (img) applyKitchenBorder(img, statusByProduct[row.dataset.productId]);
      });
    });
}

<?php if ($kitchen_poll_active): ?>
setInterval(refreshKitchenBorders, 5000);
<?php endif; ?>

<?php if ($is_active): ?>
// ---- Lọc thực đơn theo danh mục (giữ nguyên số lượng đang chọn) ----
function filterCategory(cat, btn){
  document.querySelectorAll('.menu-product, .menu-cat-heading').forEach(function(el){
    el.classList.toggle('d-none', cat !== 'all' && el.dataset.cat !== cat);
  });
  document.querySelectorAll('#categoryFilter button').forEach(function(b){
    b.classList.toggle('btn-brand', b === btn);
    b.classList.toggle('btn-outline-brand', b !== btn);
  });
}

// ---- Giỏ chọn món (chưa thêm vào đơn) ----
var addItemCart = {};

function cartCount(){
  return Object.keys(addItemCart).reduce(function(s, k){ return s + addItemCart[k]; }, 0);
}

function stepAddItemQty(pid, delta){
  var cur = Math.max(0, (addItemCart[pid] || 0) + delta);
  if (cur === 0) delete addItemCart[pid]; else addItemCart[pid] = cur;
  document.getElementById('add-item-qty-'+pid).textContent = cur;
  var badge = document.getElementById('cartCount');
  var n = cartCount();
  badge.textContent = n;
  badge.classList.toggle('d-none', n === 0);
}

function fillCartInputs(containerId, required){
  var container = document.getElementById(containerId);
  container.innerHTML = '';
  Object.keys(addItemCart).forEach(function(pid){
    container.innerHTML += '<input type="hidden" name="product_id[]" value="'+pid+'">'
      + '<input type="hidden" name="qty[]" value="'+addItemCart[pid]+'">'
      + '<input type="hidden" name="note[]" value="">';
  });
  if (required && cartCount() === 0){ alert('Vui lòng chọn ít nhất 1 món.'); return false; }
  return true;
}

// "Thông báo" gửi kèm các món đang chọn (thêm vào đơn rồi báo bếp luôn).
function prepareNotify(){
  return fillCartInputs('notifyInputs', false);
}

function blockIfCartPending(){
  if (cartCount() > 0){
    alert('Còn món đang chọn chưa thêm vào đơn — bấm "Thêm vào đơn" hoặc "Thông báo" trước.');
    return true;
  }
  return false;
}

// ---- In phiếu ngay trên trang (khổ K80) ----
function printSlip(name){
  var target = document.querySelector('.print-slip[data-slip="'+name+'"]');
  if ( ! target) return;
  document.querySelectorAll('.print-slip').forEach(function(el){ el.removeAttribute('id'); });
  target.id = 'printArea';
  var now = new Date(), pad = function(n){ return n < 10 ? '0'+n : n; };
  target.querySelectorAll('.js-print-time').forEach(function(el){
    el.textContent = pad(now.getDate())+'/'+pad(now.getMonth()+1)+'/'+now.getFullYear()+' '+pad(now.getHours())+':'+pad(now.getMinutes());
  });
  window.print();
}

function printProvisional(){
  if (blockIfCartPending()) return;
  printSlip('provisional');
}

// ---- Thanh toán ----
var ORDER_TOTAL = <?php echo (float) $order['total_amount']; ?>;

function openPayModal(){
  if (blockIfCartPending()) return;
  onPayMethodChange();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('payModal')).show();
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
