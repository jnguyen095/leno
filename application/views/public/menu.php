<?php
  // /thuc-don.html — Thực đơn: danh mục (trái trên máy tính, dãy nút trượt ngang dính trên đầu trên điện thoại) + món
  // (ảnh, tên, giá). Bấm danh mục lọc ngay (không tải lại trang); "Tất cả" hiện mọi danh mục theo từng nhóm.
  // Địa chỉ giữ danh mục đang chọn (#danh-muc-<id>) để chia sẻ / bấm Quay lại. Biến: $menu.
  $price = function ($v) { return number_format((float) $v, 0, ',', '.').'đ'; };
  $total = array_sum(array_map(function ($c) { return count($c['products']); }, $menu));
?>
<section class="pap-section pap-menu-page">
  <div class="container">
    <div class="pap-menu-head">
      <h1 class="pap-page-title mb-1"><i class="bi bi-journal-text" aria-hidden="true"></i> Thực đơn</h1>
      <?php if ($menu): ?><p class="pap-section-subtitle mb-0">Bấm vào danh mục để xem món.</p><?php endif; ?>
    </div>

    <?php if (empty($menu)): ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-cup-hot fs-1 d-block mb-2 opacity-50"></i>
        Thực đơn đang được cập nhật. Mời bạn quay lại sau nhé!
      </div>
    <?php else: ?>
    <div class="pap-menu">
      <?php // Danh mục: nav có role tablist-like nhưng dùng nút thường để đơn giản; aria-pressed cho trình đọc màn hình. ?>
      <nav class="pap-menu-cats" aria-label="Danh mục món">
        <button type="button" class="pap-menu-cat active" data-cat="all" aria-pressed="true">
          <i class="bi bi-grid" aria-hidden="true"></i><span>Tất cả</span><small><?php echo $total; ?></small>
        </button>
        <?php foreach ($menu as $c): ?>
          <button type="button" class="pap-menu-cat" data-cat="<?php echo $c['id']; ?>" aria-pressed="false">
            <span><?php echo htmlspecialchars($c['name']); ?></span><small><?php echo count($c['products']); ?></small>
          </button>
        <?php endforeach; ?>
      </nav>

      <div class="pap-menu-list">
        <?php foreach ($menu as $c): ?>
          <section class="pap-menu-group" id="danh-muc-<?php echo $c['id']; ?>" data-cat="<?php echo $c['id']; ?>" aria-labelledby="cat-title-<?php echo $c['id']; ?>">
            <h2 class="pap-menu-group-title" id="cat-title-<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> <small><?php echo count($c['products']); ?> món</small></h2>
            <div class="pap-menu-grid">
              <?php foreach ($c['products'] as $p): ?>
                <article class="pap-menu-item">
                  <div class="pap-menu-img<?php echo $p['image'] ? '' : ' is-empty'; ?>">
                    <?php if ($p['image']): ?>
                      <img src="<?php echo base_url('assets/'.$p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy" width="400" height="400">
                    <?php else: ?>
                      <i class="bi bi-cup-hot" aria-hidden="true"></i>
                    <?php endif; ?>
                  </div>
                  <div class="pap-menu-body">
                    <h3 class="pap-menu-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <div class="pap-menu-price"><?php echo $price($p['price']); ?></div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($menu): ?>
<script>
(function(){
  var buttons = document.querySelectorAll('.pap-menu-cat'), groups = document.querySelectorAll('.pap-menu-group');
  var bar = document.querySelector('.pap-menu-cats'), header = document.querySelector('.pap-header');

  // Thanh danh mục (điện thoại) dính ngay dưới header — header cao khác nhau tuỳ máy nên đo thật.
  function setOffset(){ document.documentElement.style.setProperty('--pap-header-h', (header ? header.offsetHeight : 0) + 'px'); }
  setOffset(); window.addEventListener('resize', setOffset);

  function select(cat, push){
    buttons.forEach(function(b){ var on = b.dataset.cat === cat; b.classList.toggle('active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
    groups.forEach(function(g){ g.hidden = cat !== 'all' && g.dataset.cat !== cat; });
    // Đưa nút đang chọn vào giữa dãy trượt ngang (điện thoại).
    var active = bar.querySelector('.pap-menu-cat.active');
    if (active && bar.scrollWidth > bar.clientWidth) bar.scrollTo({ left: active.offsetLeft - (bar.clientWidth - active.offsetWidth) / 2, behavior: 'smooth' });
    if (push !== false) history.replaceState(null, '', cat === 'all' ? location.pathname + location.search : '#danh-muc-' + cat);
    // Về đầu danh sách món để khách thấy ngay món của danh mục vừa chọn.
    var list = document.querySelector('.pap-menu-list'), top = list.getBoundingClientRect().top + window.scrollY - (header ? header.offsetHeight : 0) - (getComputedStyle(bar).position === 'sticky' && window.innerWidth < 992 ? bar.offsetHeight : 0) - 12;
    if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' });
    if (typeof papTrack === 'function') papTrack('menu_category', { category: cat });
  }

  buttons.forEach(function(b){ b.addEventListener('click', function(){ select(b.dataset.cat); }); });
  // Mở link có sẵn danh mục (#danh-muc-6) hoặc đổi địa chỉ / bấm Quay lại trên cùng trang.
  function fromHash(){
    var m = location.hash.match(/^#danh-muc-(\d+)$/);
    select(m && document.querySelector('.pap-menu-cat[data-cat="' + m[1] + '"]') ? m[1] : 'all', false);
  }
  window.addEventListener('hashchange', fromHash);
  if (location.hash) fromHash();
})();
</script>
<?php endif; ?>
