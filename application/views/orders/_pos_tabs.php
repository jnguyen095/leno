<?php
  // Thanh tab POS: "Bàn" (sơ đồ bàn) | "Thực đơn" (đơn đang chọn). Ở tab Thực đơn có thêm
  // dãy chip các đơn đang phục vụ để chuyển nhanh giữa nhiều khách cùng lúc.
  // Biến: $pos_tab ('tables'|'menu'), $pos_order_id, $pos_orders (Order_model::get_active_orders()).
  $menu_url = $pos_order_id ? site_url('me/orders/'.$pos_order_id) : NULL;
?>
<ul class="nav nav-tabs pos-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?php echo $pos_tab === 'tables' ? 'active fw-semibold' : ''; ?>" href="<?php echo site_url('me/tables'); ?>">
      <i class="bi bi-grid-3x3-gap"></i> Bàn
    </a>
  </li>
  <li class="nav-item">
    <?php if ($menu_url): ?>
      <a class="nav-link <?php echo $pos_tab === 'menu' ? 'active fw-semibold' : ''; ?>" href="<?php echo $menu_url; ?>">
        <i class="bi bi-journal-text"></i> Thực đơn
      </a>
    <?php else: ?>
      <span class="nav-link disabled" title="Chọn bàn trước"><i class="bi bi-journal-text"></i> Thực đơn</span>
    <?php endif; ?>
  </li>
  <li class="nav-item ms-auto d-flex align-items-center pb-1">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="posFocusBtn" onclick="togglePosFocus()">
      <i class="bi bi-arrows-fullscreen"></i> <span>Toàn màn hình</span>
    </button>
  </li>
</ul>

<script>
// Toàn màn hình cho POS. Trình duyệt luôn thoát fullscreen khi chuyển trang, nên khi bật,
// trang hiện tại KHÔNG chuyển đi nữa: nó vào fullscreen và mở một khung (iframe) phủ kín màn
// hình; mọi thao tác POS sau đó (Bàn, Thực đơn, chọn bàn, Thông báo, In tạm tính, Thanh toán...)
// đều chuyển trang BÊN TRONG khung nên fullscreen giữ nguyên. Chỉ tắt khi bấm lại nút này
// (hoặc phím Esc — trình duyệt luôn cho phép, không chặn được); khi tắt, trang chính mở đúng
// trang đang xem trong khung.
(function(){
  var shell = null;
  try { shell = (window.self !== window.top) ? window.parent.LenoPosShell : null; } catch (e) {}

  var btn = document.getElementById('posFocusBtn');
  if (shell){
    btn.querySelector('i').className = 'bi bi-fullscreen-exit';
    btn.querySelector('span').textContent = 'Thoát toàn màn hình';
  }

  window.togglePosFocus = function(){
    if (shell){ shell.exit(); return; }
    openShell();
  };

  function isFullscreen(){ return !! (document.fullscreenElement || document.webkitFullscreenElement); }

  function openShell(){
    if (window.LenoPosShell) return;
    var root = document.documentElement;
    if (root.requestFullscreen){ root.requestFullscreen().catch(function(){}); }
    else if (root.webkitRequestFullscreen){ root.webkitRequestFullscreen(); }

    var overlay = document.createElement('div');
    overlay.className = 'pos-shell';
    overlay.innerHTML = '<iframe class="pos-shell-frame" title="POS"></iframe>'
      + '<button type="button" class="btn btn-sm btn-dark pos-shell-exit d-none"><i class="bi bi-fullscreen-exit"></i> Thoát toàn màn hình</button>';
    document.body.appendChild(overlay);
    document.body.classList.add('pos-shell-open');
    var frame = overlay.querySelector('iframe');
    var exitBtn = overlay.querySelector('.pos-shell-exit');
    var originalTitle = document.title;
    var closing = false;

    window.LenoPosShell = {
      exit: function(){
        if (closing) return;
        closing = true;
        var url = location.href;
        try { url = frame.contentWindow.location.href; } catch (e) {}
        if (isFullscreen()) (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        location.href = url;   // mở đúng trang đang xem trong khung, có lại thanh menu
      }
    };

    // Trang trong khung không có nút "Thoát toàn màn hình" (vd Chuyển bàn, Gộp bàn) -> hiện nút nổi.
    frame.addEventListener('load', function(){
      try {
        var doc = frame.contentDocument;
        exitBtn.classList.toggle('d-none', !! doc.getElementById('posFocusBtn'));
        document.title = doc.title || originalTitle;
      } catch (e) { exitBtn.classList.remove('d-none'); }
    });
    exitBtn.addEventListener('click', function(){ window.LenoPosShell.exit(); });

    // Thoát fullscreen bằng Esc / nút của trình duyệt = tắt luôn khung POS.
    ['fullscreenchange', 'webkitfullscreenchange'].forEach(function(evt){
      document.addEventListener(evt, function(){ if ( ! isFullscreen()) window.LenoPosShell.exit(); });
    });

    frame.src = location.href;
  }
})();
</script>

<?php if ($pos_tab === 'menu' && count($pos_orders) > 1): ?>
<div class="d-flex flex-wrap gap-2 mb-3 pos-order-chips">
  <?php foreach ($pos_orders as $po): ?>
    <a href="<?php echo site_url('me/orders/'.$po['id']); ?>"
       class="btn btn-sm <?php echo (int) $po['id'] === (int) $pos_order_id ? 'btn-brand' : 'btn-outline-secondary'; ?>">
      <?php if ( ! empty($po['is_takeaway']) || empty($po['table_id'])): ?><i class="bi bi-bag-check"></i><?php endif; ?>
      <?php echo htmlspecialchars($po['table_name'] ?: 'Mang đi #'.$po['order_no']); ?>
      <span class="opacity-75 small"><?php echo money_format_vnd($po['total_amount']); ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
