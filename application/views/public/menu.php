<?php
  // /thuc-don.html — Thực đơn: ô tìm món trên cùng + danh mục (cột dọc bên trái, cả điện thoại lẫn máy tính) + món
  // (điện thoại: mỗi món một hàng — ảnh, tên, giá; máy tính: thẻ). Bấm danh mục lọc ngay (không tải lại trang);
  // "Tất cả" hiện mọi danh mục theo từng nhóm. Tìm món: gõ có dấu / không dấu đều được, tìm trên mọi danh mục.
  // Địa chỉ giữ danh mục đang chọn (#danh-muc-<id>) để chia sẻ / bấm Quay lại. Biến: $menu.
  $price = function ($v) { return number_format((float) $v, 0, ',', '.').'đ'; };
  $total = array_sum(array_map(function ($c) { return count($c['products']); }, $menu));
?>
<section class="pap-section pap-menu-page">
  <div class="container">
    <div class="pap-menu-head">
      <h1 class="pap-page-title mb-1"><i class="bi bi-journal-text" aria-hidden="true"></i> Thực đơn</h1>
      <?php if ($menu): ?><p class="pap-section-subtitle mb-0">Bấm vào danh mục hoặc gõ tên món để tìm.</p><?php endif; ?>
    </div>

    <?php if (empty($menu)): ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-cup-hot fs-1 d-block mb-2 opacity-50"></i>
        Thực đơn đang được cập nhật. Mời bạn quay lại sau nhé!
      </div>
    <?php else: ?>
    <div class="pap-menu-search" role="search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" id="papMenuSearch" placeholder="Tìm món… (vd: cà phê, trà sữa)" aria-label="Tìm món" autocomplete="off" enterkeyhint="search">
      <button type="button" class="pap-menu-search-clear" aria-label="Xoá tìm kiếm" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>

    <div class="pap-menu">
      <?php // Danh mục: nút thường cho đơn giản; aria-pressed cho trình đọc màn hình. ?>
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
        <div class="pap-menu-empty" hidden>
          <i class="bi bi-search fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
          Không tìm thấy món phù hợp. Bạn thử gõ từ khác nhé!
        </div>
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
  var input = document.getElementById('papMenuSearch'), clearBtn = document.querySelector('.pap-menu-search-clear');
  var empty = document.querySelector('.pap-menu-empty'), current = 'all', trackTimer;

  // Bỏ dấu tiếng Việt + chữ thường: "Cà Phê Sữa" → "ca phe sua", để khách gõ không dấu vẫn ra.
  function norm(s){ return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/\s+/g, ' ').trim(); }
  document.querySelectorAll('.pap-menu-item').forEach(function(it){ it.dataset.search = norm(it.querySelector('.pap-menu-name').textContent); });

  // Cột danh mục dính ngay dưới header — header cao khác nhau tuỳ máy nên đo thật.
  function setOffset(){ document.documentElement.style.setProperty('--pap-header-h', (header ? header.offsetHeight : 0) + 'px'); }
  setOffset(); window.addEventListener('resize', setOffset);

  // Hiện món theo danh mục đang chọn + từ khoá (mọi từ gõ vào đều phải có trong tên món).
  function apply(){
    var words = norm(input.value).split(' ').filter(Boolean), any = false;
    groups.forEach(function(g){
      var shown = 0;
      g.querySelectorAll('.pap-menu-item').forEach(function(it){
        var ok = words.every(function(w){ return it.dataset.search.indexOf(w) !== -1; });
        it.hidden = !ok; if (ok) shown++;
      });
      g.hidden = (current !== 'all' && g.dataset.cat !== current) || shown === 0;
      if (!g.hidden) any = true;
    });
    empty.hidden = any;
    clearBtn.hidden = !input.value;
  }

  function scrollToList(){
    var list = document.querySelector('.pap-menu-list'), top = list.getBoundingClientRect().top + window.scrollY - (header ? header.offsetHeight : 0) - 12;
    if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' });
  }

  function select(cat, push){
    current = cat;
    buttons.forEach(function(b){ var on = b.dataset.cat === cat; b.classList.toggle('active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
    apply();
    // Đưa nút đang chọn vào tầm nhìn trong cột danh mục (cột cuộn riêng khi nhiều danh mục).
    var active = bar.querySelector('.pap-menu-cat.active');
    if (active && bar.scrollHeight > bar.clientHeight) bar.scrollTo({ top: active.offsetTop - (bar.clientHeight - active.offsetHeight) / 2, behavior: 'smooth' });
    if (push !== false) history.replaceState(null, '', cat === 'all' ? location.pathname + location.search : '#danh-muc-' + cat);
    // Về đầu danh sách món để khách thấy ngay món của danh mục vừa chọn.
    if (push !== false) scrollToList();
    if (push !== false && typeof papTrack === 'function') papTrack('menu_category', { category: cat });
  }

  buttons.forEach(function(b){ b.addEventListener('click', function(){ select(b.dataset.cat); }); });

  // Gõ tìm: tìm trên mọi danh mục (chuyển về "Tất cả") để khách không bỏ sót món.
  input.addEventListener('input', function(){
    if (input.value.trim() && current !== 'all') { select('all', false); history.replaceState(null, '', location.pathname + location.search); } else apply();
    clearTimeout(trackTimer);
    if (input.value.trim() && typeof papTrack === 'function') trackTimer = setTimeout(function(){ papTrack('menu_search', { search_term: input.value.trim() }); }, 1200);
  });
  input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { input.blur(); scrollToList(); } });
  clearBtn.addEventListener('click', function(){ input.value = ''; apply(); input.focus(); });

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
