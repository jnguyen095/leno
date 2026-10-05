<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-bag-check"></i> Bán mang đi</h4>
    <a href="<?php echo site_url('me/orders'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Đơn hàng</a>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <?php echo form_open('me/takeaway/create', array('id' => 'takeawayForm')); ?>
      <?php foreach ($products_by_category as $cat_name => $products): ?>
        <h6 class="fw-bold text-brand mt-3 mb-2"><?php echo htmlspecialchars($cat_name); ?></h6>
        <div class="menu-list mb-2">
          <?php foreach ($products as $p): ?>
          <div class="menu-item">
            <?php if ($p['image']): ?>
              <img src="<?php echo base_url('assets/'.$p['image']); ?>" alt="" class="menu-item-thumb">
            <?php else: ?>
              <div class="menu-item-thumb-fallback"><i class="bi bi-cup-straw"></i></div>
            <?php endif; ?>
            <div class="menu-item-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
            <div class="menu-item-price"><?php echo money_format_vnd($p['price']); ?></div>
            <div class="qty-stepper">
              <button type="button" onclick="stepQty(<?php echo $p['id']; ?>,-1)"><i class="bi bi-dash-lg"></i></button>
              <span id="qty-<?php echo $p['id']; ?>">0</span>
              <button type="button" onclick="stepQty(<?php echo $p['id']; ?>,1)"><i class="bi bi-plus-lg"></i></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <div id="hiddenInputs"></div>
      <?php echo form_close(); ?>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4" style="position:sticky; top:70px;">
        <div class="card-header bg-white fw-semibold">Đơn mang đi</div>
        <div class="list-group list-group-flush" id="cartSummary">
          <div class="list-group-item text-muted text-center py-4">Chưa chọn món nào</div>
        </div>
        <div class="card-footer bg-white">
          <div class="d-flex justify-content-between fw-bold fs-5 mb-2">
            <span>Tạm tính</span><span id="cartTotal" class="text-brand">0đ</span>
          </div>
          <button type="submit" form="takeawayForm" class="btn btn-brand btn-lg w-100" onclick="return buildCartInputs();">
            <i class="bi bi-send"></i> Tạo đơn
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var PRODUCTS = <?php
  $flat = array();
  foreach ($products_by_category as $plist) { foreach ($plist as $p) { $flat[$p['id']] = array('name' => $p['product_name'], 'price' => (float) $p['price']); } }
  echo json_encode($flat, JSON_UNESCAPED_UNICODE);
?>;
var cart = {};

function fmt(n){ return Math.round(n).toLocaleString('vi-VN')+'đ'; }

function stepQty(pid, delta){
  var cur = cart[pid] || 0;
  cur = Math.max(0, cur + delta);
  if (cur === 0) delete cart[pid]; else cart[pid] = cur;
  document.getElementById('qty-'+pid).textContent = cur;
  renderSummary();
}

function renderSummary(){
  var ids = Object.keys(cart);
  var total = 0;
  var html = '';
  ids.forEach(function(pid){
    var qty = cart[pid];
    var p = PRODUCTS[pid];
    total += qty * p.price;
    html += '<div class="list-group-item d-flex justify-content-between align-items-center">'+
      '<span>'+p.name+' <span class="text-muted small">x'+qty+'</span></span>'+
      '<span class="fw-semibold">'+fmt(qty * p.price)+'</span></div>';
  });
  document.getElementById('cartSummary').innerHTML = html || '<div class="list-group-item text-muted text-center py-4">Chưa chọn món nào</div>';
  document.getElementById('cartTotal').textContent = fmt(total);
}

function buildCartInputs(){
  var container = document.getElementById('hiddenInputs');
  container.innerHTML = '';
  var count = 0;
  Object.keys(cart).forEach(function(pid){
    count++;
    container.innerHTML += '<input type="hidden" name="product_id[]" value="'+pid+'">';
    container.innerHTML += '<input type="hidden" name="qty[]" value="'+cart[pid]+'">';
    container.innerHTML += '<input type="hidden" name="note[]" value="">';
  });
  if (count === 0){ alert('Vui lòng chọn ít nhất 1 món.'); return false; }
  return true;
}
</script>
