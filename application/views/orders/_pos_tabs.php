<?php
  // Thanh tab POS: "Bàn" (sơ đồ bàn) | "Thực đơn" (đơn đang chọn). Ở tab Thực đơn có thêm
  // dãy chip các đơn đang phục vụ để chuyển nhanh giữa nhiều khách cùng lúc.
  // Biến: $pos_tab ('tables'|'menu'), $pos_order_id, $pos_orders (Order_model::get_active_orders()).
  // Tuỳ chọn (trang đơn truyền vào): $pos_tab_title = HTML tên bàn đặt ngay sau 2 tab,
  // $pos_tab_actions = HTML nút đặt trước nút "Toàn màn hình" (vd dropdown "Khác").
  $menu_url = $pos_order_id ? site_url('me/orders/'.$pos_order_id) : NULL;
  $pos_tab_title = isset($pos_tab_title) ? $pos_tab_title : '';
  $pos_tab_actions = isset($pos_tab_actions) ? $pos_tab_actions : '';
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
  <?php if ($pos_tab_title !== ''): ?>
  <li class="nav-item d-flex align-items-center ms-2 pos-tab-title"><?php echo $pos_tab_title; ?></li>
  <?php endif; ?>
  <li class="nav-item ms-auto d-flex align-items-center gap-2 pb-1">
    <?php echo $pos_tab_actions; ?>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="posFocusBtn" onclick="togglePosFocus()">
      <i class="bi bi-arrows-fullscreen"></i> <span>Toàn màn hình</span>
    </button>
  </li>
</ul>

<?php // Hộp xác nhận trong trang — thay confirm() của trình duyệt (hộp thoại gốc làm mất toàn màn hình). ?>
<div class="modal fade" id="posConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center pt-4">
        <i class="bi bi-exclamation-circle text-danger fs-1"></i>
        <div class="fs-6 fw-semibold mt-2" id="posConfirmMessage"></div>
      </div>
      <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Không</button>
        <button type="button" class="btn btn-danger px-4" id="posConfirmOk">Đồng ý</button>
      </div>
    </div>
  </div>
</div>

<script>
// In một phiếu K80 đang ẩn trong trang (.print-slip[data-slip=name]): phiếu bếp, tạm tính, hóa đơn.
// In ngay trong trang để không mở tab mới / không mất toàn màn hình.
window.posPrintSlip = function(name){
  var target = document.querySelector('.print-slip[data-slip="' + name + '"]');
  if ( ! target) return;
  document.querySelectorAll('.print-slip').forEach(function(el){ el.removeAttribute('id'); });
  target.id = 'printArea';
  var now = new Date(), pad = function(n){ return n < 10 ? '0' + n : n; };
  target.querySelectorAll('.js-print-time').forEach(function(el){
    el.textContent = pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
  });
  window.print();
};
</script>

<script>
// posConfirm('Hủy món này?').then(function(ok){ if (ok) ... }) — xác nhận bằng modal Bootstrap.
window.posConfirm = function(message, okLabel){
  return new Promise(function(resolve){
    var el = document.getElementById('posConfirmModal');
    var okBtn = document.getElementById('posConfirmOk');
    var modal = bootstrap.Modal.getOrCreateInstance(el);
    var answered = false;
    document.getElementById('posConfirmMessage').textContent = message;
    okBtn.textContent = okLabel || 'Đồng ý';
    okBtn.onclick = function(){ answered = true; modal.hide(); };
    el.addEventListener('hidden.bs.modal', function onHidden(){
      el.removeEventListener('hidden.bs.modal', onHidden);
      okBtn.onclick = null;
      resolve(answered);
    });
    modal.show();
    el.addEventListener('shown.bs.modal', function onShown(){
      el.removeEventListener('shown.bs.modal', onShown);
      okBtn.focus();
    });
  });
};
</script>

<?php // Hộp nhập ghi chú trong trang (bàn / đơn / món) — thay prompt() của trình duyệt. ?>
<div class="modal fade" id="posPromptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-semibold" id="posPromptTitle"></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 mb-2" id="posPromptChips"></div>
        <textarea class="form-control" id="posPromptInput" rows="3" maxlength="255" placeholder="Nhập ghi chú…"></textarea>
        <div class="form-text">Để trống rồi Lưu để xoá ghi chú.</div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-brand px-4" id="posPromptOk"><i class="bi bi-check2"></i> Lưu</button>
      </div>
    </div>
  </div>
</div>

<script>
// posPrompt('Ghi chú món', 'Ít đá', ['Ít đá', 'Không đường']).then(function(text){ if (text !== null) ... })
// Trả về chuỗi đã nhập (có thể rỗng = xoá) hoặc null nếu bấm Hủy. Chip gợi ý bấm để thêm/bỏ.
window.posPrompt = function(title, value, suggestions){
  return new Promise(function(resolve){
    var el = document.getElementById('posPromptModal');
    var input = document.getElementById('posPromptInput');
    var chips = document.getElementById('posPromptChips');
    var okBtn = document.getElementById('posPromptOk');
    var modal = bootstrap.Modal.getOrCreateInstance(el);
    var saved = false;

    document.getElementById('posPromptTitle').textContent = title;
    input.value = value || '';

    function parts(){ return input.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean); }
    function paintChips(){
      var cur = parts().map(function(s){ return s.toLowerCase(); });
      chips.querySelectorAll('button').forEach(function(b){
        var on = cur.indexOf(b.dataset.value.toLowerCase()) !== -1;
        b.classList.toggle('btn-brand', on);
        b.classList.toggle('btn-outline-secondary', ! on);
      });
    }
    chips.innerHTML = '';
    (suggestions || []).forEach(function(s){
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm btn-outline-secondary rounded-pill';
      b.textContent = s;
      b.dataset.value = s;
      b.onclick = function(){
        var list = parts(), i = list.map(function(x){ return x.toLowerCase(); }).indexOf(s.toLowerCase());
        if (i === -1) list.push(s); else list.splice(i, 1);
        input.value = list.join(', ');
        paintChips();
        input.focus();
      };
      chips.appendChild(b);
    });
    chips.classList.toggle('d-none', ! (suggestions && suggestions.length));
    input.oninput = paintChips;
    paintChips();

    okBtn.onclick = function(){ saved = true; modal.hide(); };
    input.onkeydown = function(e){ if (e.key === 'Enter' && ! e.shiftKey){ e.preventDefault(); okBtn.click(); } };
    el.addEventListener('hidden.bs.modal', function onHidden(){
      el.removeEventListener('hidden.bs.modal', onHidden);
      okBtn.onclick = null;
      resolve(saved ? input.value.trim() : null);
    });
    el.addEventListener('shown.bs.modal', function onShown(){
      el.removeEventListener('shown.bs.modal', onShown);
      input.focus();
      input.setSelectionRange(input.value.length, input.value.length);
    });
    modal.show();
  });
};
</script>

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

<?php if ($pos_tab === 'menu'): ?>
<?php // Swiper cho các dãy chip 1 hàng ở tab Thực đơn (chip đơn đang phục vụ, lọc danh mục món). ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.2.10/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11.2.10/swiper-bundle.min.js"></script>
<script>
// posChipSwiper(bar, activeIndex): biến .pos-order-chips-bar thành 1 hàng trượt ngang (vuốt / lăn chuột /
// kéo thanh cuộn / nút ‹ ›). Nút ‹ › tự ẩn khi đủ chỗ hiện hết; chip activeIndex được cuộn vào tầm nhìn.
window.posChipSwiper = function(bar, activeIndex){
  if ( ! bar || typeof Swiper === 'undefined') return null; // CDN lỗi -> vẫn cuộn ngang được bằng CSS
  var swiper = new Swiper(bar.querySelector('.swiper'), {
    slidesPerView: 'auto',
    spaceBetween: 8,
    freeMode: { enabled: true, momentumRatio: 0.6 },
    mousewheel: { forceToAxis: true },
    grabCursor: true,
    watchOverflow: true,
    navigation: { prevEl: bar.querySelector('.pos-chips-prev'), nextEl: bar.querySelector('.pos-chips-next') },
    scrollbar: { el: bar.querySelector('.pos-chips-scrollbar'), draggable: true, hide: false },
    on: { init: function(){ bar.classList.add('is-ready'); } }
  });
  if (activeIndex > 0 && ! swiper.isLocked) swiper.slideTo(Math.max(0, activeIndex - 1), 0);
  return swiper;
};
</script>
<?php endif; ?>

<?php // Luôn hiện ở tab Thực đơn (kể cả chỉ 1 đơn) để thấy đang gọi món cho bàn nào. ?>
<?php if ($pos_tab === 'menu' && $pos_orders): ?>
<?php
  $active_chip_index = 0;
  foreach ($pos_orders as $i => $po) { if ((int) $po['id'] === (int) $pos_order_id) $active_chip_index = $i; }
?>
<div class="pos-order-chips-bar mb-3" id="posOrderChipsBar">
  <button type="button" class="pos-chips-nav pos-chips-prev" aria-label="Đơn trước"><i class="bi bi-chevron-left"></i></button>
  <div class="swiper pos-order-chips">
    <div class="swiper-wrapper">
      <?php foreach ($pos_orders as $po): ?>
      <div class="swiper-slide">
        <a href="<?php echo site_url('me/orders/'.$po['id']); ?>"
           class="btn btn-sm <?php echo (int) $po['id'] === (int) $pos_order_id ? 'btn-brand' : 'btn-outline-secondary'; ?>">
          <?php if ( ! empty($po['is_takeaway']) || empty($po['table_id'])): ?><i class="bi bi-bag-check"></i><?php endif; ?>
          <?php echo htmlspecialchars($po['table_name'] ?: 'Mang đi #'.$po['order_no']); ?>
          <span class="opacity-75 small"><?php echo money_format_vnd($po['total_amount']); ?></span>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="swiper-scrollbar pos-chips-scrollbar"></div>
  </div>
  <button type="button" class="pos-chips-nav pos-chips-next" aria-label="Đơn sau"><i class="bi bi-chevron-right"></i></button>
</div>
<script>posChipSwiper(document.getElementById('posOrderChipsBar'), <?php echo (int) $active_chip_index; ?>);</script>
<?php endif; ?>
