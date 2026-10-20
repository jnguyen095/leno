<?php
  // Một thẻ tin (trang chủ, /tin-tuc.html, "Tin khác" dưới bài). Cả thẻ bấm được. Biến: $n (dòng pages loại NEWS).
  $thumb = Page_model::thumbnail($n);
  $is_new = $n['published_at'] && strtotime($n['published_at']) > strtotime('-7 days');
?>
<a class="pap-news-card" href="<?php echo site_url(Page_model::public_path($n)); ?>" onclick="papTrack('click_news', {news: '<?php echo $n['code']; ?>'})">
  <span class="pap-news-thumb<?php echo $thumb ? '' : ' is-empty'; ?>">
    <?php if ($thumb): ?>
      <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" loading="lazy" width="640" height="360">
    <?php else: ?>
      <i class="bi bi-newspaper" aria-hidden="true"></i>
    <?php endif; ?>
    <?php if ((int) $n['is_pinned']): ?>
      <span class="pap-news-badge pap-news-badge-pin"><i class="bi bi-pin-angle-fill"></i> Nổi bật</span>
    <?php elseif ($is_new): ?>
      <span class="pap-news-badge">Mới</span>
    <?php endif; ?>
  </span>
  <span class="pap-news-body">
    <?php if ($n['published_at']): ?>
      <span class="pap-news-date"><i class="bi bi-calendar3" aria-hidden="true"></i> <?php echo date('d/m/Y', strtotime($n['published_at'])); ?></span>
    <?php endif; ?>
    <span class="pap-news-title"><?php echo htmlspecialchars($n['title']); ?></span>
    <span class="pap-news-excerpt"><?php echo htmlspecialchars(Page_model::excerpt($n, 140)); ?></span>
    <span class="pap-news-more">Đọc tiếp <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
  </span>
</a>
