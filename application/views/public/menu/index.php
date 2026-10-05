<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?php echo htmlspecialchars($table['table_name']); ?> - Đặt món</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo base_url('assets/css/style_v1.3.css'); ?>" rel="stylesheet">
</head>
<body style="padding-bottom:96px;">

<div class="menu-header py-2 px-3 d-flex justify-content-between align-items-center">
  <div>
    <div class="fw-bold"><i class="bi bi-cup-hot-fill"></i> <?php echo htmlspecialchars($table['table_name']); ?></div>
    <div class="small opacity-75">Chào mừng quý khách!</div>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-light btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#callStaffModal">
      <i class="bi bi-bell-fill"></i> Gọi NV
    </button>
    <button class="btn btn-light btn-sm rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#orderStatusPanel">
      <i class="bi bi-receipt"></i> Đơn của tôi
      <span id="orderItemCountBadge" class="badge bg-danger rounded-pill ms-1 d-none">0</span>
    </button>
  </div>
</div>

<!-- Call staff modal -->
<div class="modal fade" id="callStaffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-bell-fill text-brand"></i> Gọi nhân viên</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="callStaffAlert" class="alert d-none py-2 small mb-3"></div>
        <div class="d-grid gap-2">
          <button class="btn btn-outline-brand btn-lg text-start" onclick="callStaff('HELP', this);">
            <i class="bi bi-question-circle fs-4 me-2"></i> Cần hỗ trợ
          </button>
          <button class="btn btn-outline-brand btn-lg text-start" onclick="callStaff('PAYMENT', this);">
            <i class="bi bi-credit-card fs-4 me-2"></i> Yêu cầu thanh toán
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<ul class="nav category-nav px-2 py-2 gap-2 flex-nowrap">
  <?php foreach (array_keys($products_by_category) as $cat_name): ?>
    <li class="nav-item">
      <a class="btn btn-sm btn-outline-secondary" href="#cat-<?php echo md5($cat_name); ?>"><?php echo htmlspecialchars($cat_name); ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="container-fluid px-3 py-2">
  <?php foreach ($products_by_category as $cat_name => $products): ?>
  <h6 id="cat-<?php echo md5($cat_name); ?>" class="fw-bold text-brand mt-3 mb-2 pt-2"><?php echo htmlspecialchars($cat_name); ?></h6>
  <div class="menu-list mb-2">
    <?php foreach ($products as $p): ?>
    <div class="menu-item">
      <?php if ($p['image']): ?>
        <img src="<?php echo base_url('assets/'.$p['image']); ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>" class="menu-item-thumb">
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
</div>

<button class="btn btn-brand btn-lg cart-fab d-none" id="cartFab" data-bs-toggle="offcanvas" data-bs-target="#cartPanel">
  <i class="bi bi-cart-fill"></i> Xem giỏ hàng (<span id="cartCount">0</span>) — <span id="cartTotal">0đ</span>
</button>

<!-- Cart offcanvas (staging, not yet sent) -->
<div class="offcanvas offcanvas-bottom cart-offcanvas" tabindex="-1" id="cartPanel" style="height:80vh;">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Giỏ hàng</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <div class="list-group flex-grow-1 overflow-auto mb-3" id="cartItemsList"></div>
    <div class="d-flex justify-content-between fw-bold fs-5 mb-2">
      <span>Tổng cộng</span><span id="cartOffcanvasTotal">0đ</span>
    </div>
    <button class="btn btn-brand btn-lg" id="submitOrderBtn" onclick="submitOrder();"><i class="bi bi-send"></i> Gọi Nuo</button>
  </div>
</div>

<!-- Order status offcanvas (server truth, polled every 5s) -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="orderStatusPanel" style="height:80vh;">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Đơn hàng của bạn</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <div id="orderStatusContent" class="flex-grow-1 overflow-auto"><div class="text-muted text-center py-4">Đang tải...</div></div>
    <div id="orderStatusTotal" class="d-none flex-column border-top pt-2 mt-2">
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Số lượng mặt hàng</span><span id="orderStatusQtyValue">0</span>
      </div>
      <div class="d-flex justify-content-between align-items-center fw-bold fs-5">
        <span>Tổng cộng đơn hàng</span><span class="text-brand" id="orderStatusTotalValue">0đ</span>
      </div>
    </div>
  </div>
</div>

<!-- Shown when the session has ended/expired mid-visit (stale tab) -->
<div id="expiredOverlay" class="d-none" style="position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:2000; align-items:center; justify-content:center; text-align:center; padding:2rem;">
  <div class="bg-white rounded-4 p-4" style="max-width:340px;">
    <i class="bi bi-qr-code-scan text-warning" style="font-size:2.5rem;"></i>
    <p class="mt-3 mb-3" id="expiredMessage"></p>
<!--    <button class="btn btn-brand w-100" onclick="location.reload();">Tải lại trang</button>-->
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var TOKEN = '<?php echo $token; ?>';
var SECRET = '<?php echo $secret; ?>';
var BASE_URL = '<?php echo base_url(); ?>';
var pollTimer = null;

function handleExpired(message){
  if (pollTimer) clearInterval(pollTimer);
  document.getElementById('expiredMessage').textContent = message || 'Phiên đặt món đã kết thúc, vui lòng quét lại mã QR trên bàn.';
  document.getElementById('expiredOverlay').classList.remove('d-none');
  document.getElementById('expiredOverlay').classList.add('d-flex');
}
var PRODUCTS = <?php
  $flat = array();
  foreach ($products_by_category as $plist) { foreach ($plist as $p) { $flat[$p['id']] = array('name'=>$p['product_name'],'price'=>(float)$p['price'],'image'=>$p['image']); } }
  echo json_encode($flat, JSON_UNESCAPED_UNICODE);
?>;
var STORAGE_KEY = 'cart_'+TOKEN;
var cart = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

function fmt(n){ return Math.round(n).toLocaleString('vi-VN')+'đ'; }
function escapeHtml(s){ var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

function callStaff(type, btn){
  document.querySelectorAll('#callStaffModal button').forEach(function(b){ b.disabled = true; });

  fetch('<?php echo base_url('api/call/create'); ?>', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({token: TOKEN, secret: SECRET, type: type})
  }).then(function(r){ return r.json(); }).then(function(res){
    if (res.expired){ handleExpired(res.message); return; }
    var alertEl = document.getElementById('callStaffAlert');
    alertEl.textContent = res.message || (res.success ? 'Đã gửi yêu cầu.' : 'Có lỗi xảy ra, vui lòng thử lại.');
    alertEl.className = 'alert py-2 small mb-3 ' + (res.success ? 'alert-success' : 'alert-danger');
    document.querySelectorAll('#callStaffModal button').forEach(function(b){ b.disabled = false; });
    if (res.success){
      setTimeout(function(){ bootstrap.Modal.getOrCreateInstance(document.getElementById('callStaffModal')).hide(); }, 1500);
    }
  }).catch(function(){
    document.querySelectorAll('#callStaffModal button').forEach(function(b){ b.disabled = false; });
  });
}

function stepQty(pid, delta){
  var cur = cart[pid] || 0;
  cur = Math.max(0, cur + delta);
  if (cur === 0) delete cart[pid]; else cart[pid] = cur;
  document.getElementById('qty-'+pid).textContent = cur;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
  renderCart();
}

function renderCart(){
  var ids = Object.keys(cart);
  var count = 0, total = 0;
  var html = '';
  ids.forEach(function(pid){
    var qty = cart[pid];
    var p = PRODUCTS[pid];
    if (!p) return;
    count += qty;
    total += qty * p.price;
    var thumb = p.image
      ? '<img class="menu-item-thumb" src="'+BASE_URL+'assets/'+p.image+'" alt="">'
      : '<div class="menu-item-thumb-fallback"><i class="bi bi-cup-straw"></i></div>';
    html += '<div class="menu-item px-0">'+thumb+
      '<div class="menu-item-name">'+escapeHtml(p.name)+'</div>'+
      '<div class="menu-item-price">'+fmt(p.price)+'</div>'+
      '<div class="qty-stepper">'+
        '<button type="button" onclick="stepQty('+pid+',-1)"><i class="bi bi-dash-lg"></i></button><span>'+qty+'</span><button type="button" onclick="stepQty('+pid+',1)"><i class="bi bi-plus-lg"></i></button>'+
      '</div></div>';
  });
  document.getElementById('cartItemsList').innerHTML = html || '<div class="text-muted text-center py-4">Giỏ hàng trống</div>';
  document.getElementById('cartCount').textContent = count;
  document.getElementById('cartTotal').textContent = fmt(total);
  document.getElementById('cartOffcanvasTotal').textContent = fmt(total);
  document.getElementById('cartFab').classList.toggle('d-none', count === 0);
}

function submitOrder(){
  var items = Object.keys(cart).map(function(pid){ return {product_id: parseInt(pid,10), qty: cart[pid]}; });
  if (items.length === 0) return;

  var btn = document.getElementById('submitOrderBtn');
  btn.disabled = true;
  btn.textContent = 'Đang gửi...';

  fetch('<?php echo base_url('api/order/create'); ?>', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({token: TOKEN, secret: SECRET, items: items})
  }).then(function(r){ return r.json(); }).then(function(res){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send"></i> Gọi Nước';
    if (res.expired){ handleExpired(res.message); return; }
    if (res.success){
      cart = {};
      localStorage.setItem(STORAGE_KEY, '{}');
      Object.keys(PRODUCTS).forEach(function(pid){ var el = document.getElementById('qty-'+pid); if (el) el.textContent = '0'; });
      renderCart();
      loadOrderStatus();
      bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartPanel')).hide();
      bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('orderStatusPanel')).show();
    } else {
      alert(res.message || 'Có lỗi xảy ra, vui lòng thử lại.');
    }
  });
}

var STATUS_LABEL = {NEW:'Đang chờ', PREPARING:'Đang pha chế', COMPLETED:'Hoàn thành'};
var STATUS_COLOR = {NEW:'danger', PREPARING:'warning', COMPLETED:'success'};

function formatDateTime(dt){
  var d = new Date(dt.replace(' ', 'T'));
  var pad = function(n){ return n < 10 ? '0'+n : n; };
  return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear()+' '+pad(d.getHours())+':'+pad(d.getMinutes());
}

function loadOrderStatus(){
  fetch('<?php echo base_url('api/order/current-by-token'); ?>/'+TOKEN+'/'+SECRET)
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (res.expired){ handleExpired(res.message); return; }

      var el = document.getElementById('orderStatusContent');
      var totalEl = document.getElementById('orderStatusTotal');
      var badgeEl = document.getElementById('orderItemCountBadge');

      if (!res.success || !res.order){
        el.innerHTML = '<div class="text-muted text-center py-4">Chưa có món nào được gọi.</div>';
        totalEl.classList.add('d-none');
        totalEl.classList.remove('d-flex');
        badgeEl.classList.add('d-none');
        return;
      }
      var order = res.order;
      var totalQty = order.items.reduce(function(sum, it){ return sum + parseInt(it.qty, 10); }, 0);
      var html = '';

      if (order.tickets.length){
        html += '<h6 class="fw-semibold mb-2">Các đợt gọi món</h6>';
        order.tickets.slice().reverse().forEach(function(t){
          html += '<div class="card mb-2"><div class="card-body p-2">';
          html += '<div class="d-flex justify-content-between align-items-center mb-1">'+
            '<span class="badge bg-'+STATUS_COLOR[t.status]+'">'+STATUS_LABEL[t.status]+'</span>'+
            '<span class="small text-muted"><i class="bi bi-clock"></i> '+formatDateTime(t.created_at)+'</span>'+
          '</div>';
          t.items.forEach(function(it){
            var thumb = it.image
              ? '<img class="menu-item-thumb" src="'+BASE_URL+'assets/'+it.image+'" alt="">'
              : '<div class="menu-item-thumb-fallback"><i class="bi bi-cup-straw"></i></div>';
            html += '<div class="menu-item px-0">'+thumb+
              '<div class="menu-item-name">'+escapeHtml(it.product_name)+'</div>'+
              '<div class="menu-item-price">'+fmt(it.price)+'</div>'+
              '<div class="menu-item-qty">x'+it.qty+'</div>'+
            '</div>';
          });
          html += '</div></div>';
        });
      }

      if (!html){
        html = '<div class="text-muted text-center py-4">Chưa có món nào được gọi.</div>';
      }
      el.innerHTML = html;

      document.getElementById('orderStatusQtyValue').textContent = totalQty + ' món';
      document.getElementById('orderStatusTotalValue').textContent = fmt(order.total_amount);
      totalEl.classList.remove('d-none');
      totalEl.classList.add('d-flex');

      badgeEl.textContent = totalQty;
      badgeEl.classList.toggle('d-none', totalQty === 0);
    });
}

renderCart();
loadOrderStatus();
pollTimer = setInterval(loadOrderStatus, 5000);
</script>
</body>
</html>
