<div class="container-fluid py-3 py-md-4">
  <?php
    // Trạng thái pha chế theo từng sản phẩm (ưu tiên NEW > PREPARING > COMPLETED trong
    // mọi ticket của đơn này) — dùng để tô viền ảnh món trong danh sách "Món đã gọi".
    // Chỉ tính khi đơn còn hoạt động, khớp điều kiện hiển thị của khối ticket status cũ.
    $kitchen_status_by_product = array();
    $kitchen_poll_active = ($tickets && ! in_array($order['status'], array('PAID', 'CANCELLED'), TRUE));
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
  ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">
        <?php if ($order['order_type'] === 'TAKEAWAY'): ?>
          <i class="bi bi-bag-check text-brand"></i> Mang đi
        <?php else: ?>
          <?php echo htmlspecialchars($order['table_name']); ?>
        <?php endif; ?>
        <span class="text-muted fs-6">#<?php echo htmlspecialchars($order['order_no']); ?></span>
      </h4>
      <span class="badge bg-<?php echo order_status_badge($order['status']); ?>"><?php echo $order['status']; ?></span>
    </div>
    <div class="btn-group btn-group-sm flex-wrap">
      <?php if ($order['order_type'] === 'TAKEAWAY'): ?>
        <?php if ($order['status'] === 'OPEN'): ?>
        <?php echo form_open('me/orders/'.$order['id'].'/checkout', array('class' => 'd-inline')); ?>
          <button class="btn btn-brand"><i class="bi bi-cash-coin"></i> Thanh toán</button>
        <?php echo form_close(); ?>
        <?php else: ?>
        <a href="<?php echo site_url('me/cashier/'.$order['id']); ?>" class="btn btn-brand"><i class="bi bi-cash-coin"></i> Thu ngân</a>
        <?php endif; ?>
        <a href="<?php echo site_url('me/takeaway/create'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Bán mang đi</a>
      <?php else: ?>
        <a href="<?php echo site_url('me/tables/'.$order['table_id'].'/transfer'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-right"></i> Chuyển bàn</a>
        <a href="<?php echo site_url('me/tables/'.$order['table_id'].'/merge'); ?>" class="btn btn-outline-secondary"><i class="bi bi-union"></i> Gộp bàn</a>
        <a href="<?php echo site_url('me/tables/'.$order['table_id'].'/print-provisional'); ?>" target="_blank" class="btn btn-outline-dark"><i class="bi bi-printer"></i> In tạm tính</a>
        <a href="<?php echo site_url('me/tables'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Sơ đồ bàn</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white fw-semibold">Món đã gọi</div>
        <div class="list-group list-group-flush" id="orderedItemsList">
          <?php foreach ($items as $it): ?>
            <?php $this->load->view('orders/_item_row', array('it' => $it, 'order' => $order, 'kitchen_status' => isset($kitchen_status_by_product[$it['product_id']]) ? $kitchen_status_by_product[$it['product_id']] : NULL)); ?>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
            <div class="list-group-item text-muted text-center py-4">Chưa có món nào.</div>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white">
          <div class="d-flex justify-content-between small"><span>Tạm tính</span><span><?php echo money_format_vnd($order['subtotal']); ?></span></div>
          <div class="d-flex justify-content-between small"><span>Giảm giá</span><span>-<?php echo money_format_vnd($order['discount_amount']); ?></span></div>
          <div class="d-flex justify-content-between small"><span>VAT</span><span><?php echo money_format_vnd($order['vat_amount']); ?></span></div>
          <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>Tổng cộng</span><span class="text-brand"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger py-2 small"><?php echo $this->session->flashdata('error'); ?></div>
      <?php endif; ?>

      <?php if ($order['status'] === 'OPEN'): ?>
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold">Thêm món</div>
        <div class="card-body" style="max-height:65vh; overflow-y:auto;">
          <?php echo form_open('me/orders/'.$order['id'].'/add-item', array('id' => 'addItemForm')); ?>
          <?php foreach ($products_by_category as $cat_name => $products): ?>
            <div class="fw-semibold text-brand mt-2 mb-1"><?php echo htmlspecialchars($cat_name); ?></div>
            <?php foreach ($products as $p): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div class="d-flex align-items-center gap-2">
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
          <div id="hiddenInputs"></div>
          <?php echo form_close(); ?>
        </div>
        <div class="card-footer bg-white">
          <button type="submit" form="addItemForm" class="btn btn-brand w-100" onclick="return buildCartInputs();"><i class="bi bi-send"></i> Gửi bếp</button>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-info">Đơn đã chuyển sang thu ngân, không thể thêm món.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Tô lại viền ảnh món theo trạng thái pha chế mới nhất, không render lại toàn bộ list.
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

var addItemCart = {};

function stepAddItemQty(pid, delta){
  var cur = addItemCart[pid] || 0;
  cur = Math.max(0, cur + delta);
  if (cur === 0) delete addItemCart[pid]; else addItemCart[pid] = cur;
  document.getElementById('add-item-qty-'+pid).textContent = cur;
}

function buildCartInputs(){
  var container = document.getElementById('hiddenInputs');
  container.innerHTML = '';
  var count = 0;
  Object.keys(addItemCart).forEach(function(pid){
    count++;
    container.innerHTML += '<input type="hidden" name="product_id[]" value="'+pid+'">';
    container.innerHTML += '<input type="hidden" name="qty[]" value="'+addItemCart[pid]+'">';
    container.innerHTML += '<input type="hidden" name="note[]" value="">';
  });
  if (count === 0){ alert('Vui lòng chọn ít nhất 1 món.'); return false; }
  return true;
}
</script>
