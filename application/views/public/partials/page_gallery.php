<?php
  // Bộ sưu tập ảnh cuối trang nội dung / tin (soạn ở Quản trị → Trang nội dung → Bộ sưu tập ảnh).
  // Băng chuyền: vuốt ngang / mũi tên, tự chạy chậm (dừng khi khách chạm, rê chuột hoặc tự cuộn).
  // Bấm ảnh mở xem lớn toàn màn hình: vuốt / mũi tên / phím ← → để chuyển, Esc hoặc ✕ để đóng.
  // Biến: $gallery ([image, caption]), $title (tên trang — làm chữ thay thế khi ảnh không có chú thích).
  $n = count($gallery);
?>
<section class="pap-section pap-section-soft pap-gallery" aria-labelledby="gallery-title">
  <div class="container">
    <div class="pap-gallery-head">
      <h2 id="gallery-title" class="pap-section-title mb-0"><i class="bi bi-images" aria-hidden="true"></i> Hình ảnh <small><?php echo $n; ?> ảnh</small></h2>
      <?php if ($n > 1): ?>
        <div class="pap-gallery-nav">
          <button type="button" class="pap-gallery-arrow" data-dir="-1" aria-label="Ảnh trước"><i class="bi bi-chevron-left"></i></button>
          <button type="button" class="pap-gallery-arrow" data-dir="1" aria-label="Ảnh sau"><i class="bi bi-chevron-right"></i></button>
        </div>
      <?php endif; ?>
    </div>
    <div class="pap-gallery-track" tabindex="0" aria-label="Hình ảnh — vuốt ngang để xem thêm">
      <?php foreach ($gallery as $i => $g): $alt = $g['caption'] !== '' ? $g['caption'] : $title.' – ảnh '.($i + 1); ?>
        <figure class="pap-gallery-slide">
          <button type="button" class="pap-gallery-open" data-index="<?php echo $i; ?>" aria-label="Xem ảnh lớn: <?php echo htmlspecialchars($alt); ?>">
            <img src="<?php echo htmlspecialchars(base_url('assets/'.$g['image'])); ?>" alt="<?php echo htmlspecialchars($alt); ?>" loading="lazy">
            <span class="pap-gallery-zoom" aria-hidden="true"><i class="bi bi-arrows-fullscreen"></i></span>
          </button>
          <?php if ($g['caption'] !== ''): ?><figcaption><?php echo htmlspecialchars($g['caption']); ?></figcaption><?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="pap-lightbox" id="papLightbox" role="dialog" aria-modal="true" aria-label="Xem ảnh" hidden>
  <div class="pap-lightbox-top">
    <span class="pap-lightbox-count" aria-live="polite"></span>
    <button type="button" class="pap-lightbox-btn pap-lightbox-close" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="pap-lightbox-stage">
    <?php if ($n > 1): ?><button type="button" class="pap-lightbox-btn pap-lightbox-prev" aria-label="Ảnh trước"><i class="bi bi-chevron-left"></i></button><?php endif; ?>
    <img class="pap-lightbox-img" alt="">
    <?php if ($n > 1): ?><button type="button" class="pap-lightbox-btn pap-lightbox-next" aria-label="Ảnh sau"><i class="bi bi-chevron-right"></i></button><?php endif; ?>
  </div>
  <div class="pap-lightbox-caption"></div>
</div>

<script>
(function(){
  var track = document.querySelector('.pap-gallery-track'), slides = track.querySelectorAll('.pap-gallery-slide');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Băng chuyền ----
  function step(){ return slides.length > 1 ? slides[1].offsetLeft - slides[0].offsetLeft : track.clientWidth; }
  function go(dir){
    var max = track.scrollWidth - track.clientWidth - 2;
    // Hết ảnh thì quay về đầu (và ngược lại).
    var left = dir > 0 && track.scrollLeft >= max ? 0 : dir < 0 && track.scrollLeft <= 2 ? max : track.scrollLeft + dir * step();
    track.scrollTo({ left: left, behavior: reduce ? 'auto' : 'smooth' });
  }
  document.querySelectorAll('.pap-gallery-arrow').forEach(function(b){ b.addEventListener('click', function(){ stop(); go(+b.dataset.dir); }); });

  // Tự chạy mỗi 4 giây khi băng chuyền đang trong màn hình; dừng hẳn khi khách tự xem (chạm, cuộn, bấm mũi tên).
  var timer = null, stopped = reduce || slides.length < 2, paused = false, visible = false;
  function tick(){ if ( ! stopped && ! paused && visible && document.getElementById('papLightbox').hidden && track.scrollWidth > track.clientWidth + 2) go(1); }
  function stop(){ stopped = true; clearInterval(timer); }
  if ( ! stopped) {
    timer = setInterval(tick, 4000);
    ['pointerdown', 'wheel', 'touchstart', 'keydown'].forEach(function(ev){ track.addEventListener(ev, stop, { passive: true }); });
    track.addEventListener('mouseenter', function(){ paused = true; });
    track.addEventListener('mouseleave', function(){ paused = false; });
    if ('IntersectionObserver' in window) new IntersectionObserver(function(es){ visible = es[0].isIntersecting; }, { threshold: .4 }).observe(track);
    else visible = true;
  }

  // ---- Xem ảnh lớn ----
  var box = document.getElementById('papLightbox'), img = box.querySelector('.pap-lightbox-img');
  var count = box.querySelector('.pap-lightbox-count'), cap = box.querySelector('.pap-lightbox-caption');
  var items = Array.prototype.map.call(slides, function(s){
    var i = s.querySelector('img'), c = s.querySelector('figcaption');
    return { src: i.src, alt: i.alt, caption: c ? c.textContent : '' };
  });
  var current = 0, opener = null;
  function show(i){
    current = (i + items.length) % items.length;
    img.src = items[current].src; img.alt = items[current].alt;
    count.textContent = (current + 1) + ' / ' + items.length;
    cap.textContent = items[current].caption; cap.hidden = ! items[current].caption;
  }
  function open(i, btn){
    opener = btn; show(i); box.hidden = false;
    document.documentElement.classList.add('pap-lightbox-on');
    box.querySelector('.pap-lightbox-close').focus();
    if (typeof papTrack === 'function') papTrack('gallery_open', { index: i + 1 });
  }
  function close(){
    box.hidden = true; document.documentElement.classList.remove('pap-lightbox-on');
    // Đưa băng chuyền tới ảnh đang xem để khách đóng lại thấy đúng chỗ.
    var s = slides[current]; if (s) track.scrollTo({ left: s.offsetLeft - slides[0].offsetLeft, behavior: 'auto' });
    var b = s ? s.querySelector('.pap-gallery-open') : opener;
    if (b) b.focus({ preventScroll: true });
  }
  track.querySelectorAll('.pap-gallery-open').forEach(function(b){ b.addEventListener('click', function(){ stop(); open(+b.dataset.index, b); }); });
  box.querySelector('.pap-lightbox-close').addEventListener('click', close);
  var prev = box.querySelector('.pap-lightbox-prev'), next = box.querySelector('.pap-lightbox-next');
  if (prev) prev.addEventListener('click', function(){ show(current - 1); });
  if (next) next.addEventListener('click', function(){ show(current + 1); });
  // Bấm ra vùng tối ngoài ảnh để đóng.
  box.addEventListener('click', function(e){ if (e.target === box || e.target.classList.contains('pap-lightbox-stage')) close(); });
  document.addEventListener('keydown', function(e){
    if (box.hidden) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft' && items.length > 1) show(current - 1);
    else if (e.key === 'ArrowRight' && items.length > 1) show(current + 1);
    else if (e.key === 'Tab') { // giữ phím Tab trong hộp xem ảnh
      var f = box.querySelectorAll('button'), first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if ( ! e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });
  // Vuốt trái / phải trên điện thoại để chuyển ảnh.
  var x0 = null, y0 = null;
  box.addEventListener('touchstart', function(e){ x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
  box.addEventListener('touchend', function(e){
    if (x0 === null || items.length < 2) return;
    var dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(current + (dx < 0 ? 1 : -1));
    x0 = null;
  });
})();
</script>
