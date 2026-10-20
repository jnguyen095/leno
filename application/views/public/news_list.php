<?php
  // /tin-tuc.html — mọi tin đang hiện, tin ghim trước, mới trước; 12 tin một trang. Biến: $news, $paging.
  $page_url = function ($n) { return site_url('tin-tuc').($n > 1 ? '?trang='.$n : ''); };
?>
<section class="pap-section">
  <div class="container">
    <h1 class="pap-page-title text-center mb-1"><i class="bi bi-megaphone-fill text-primary" aria-hidden="true"></i> Tin tức</h1>
    <p class="pap-section-subtitle text-center mb-4">Tin mới, sự kiện và ưu đãi.</p>

    <?php if (empty($news)): ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-newspaper fs-1 d-block mb-2 opacity-50"></i>
        Chưa có tin nào. Mời bạn quay lại sau nhé!
        <div class="mt-3"><a href="<?php echo base_url(); ?>" class="btn pap-btn-primary"><i class="bi bi-house-door"></i> Về trang chủ</a></div>
      </div>
    <?php else: ?>
      <div class="pap-news-grid">
        <?php foreach ($news as $n) $this->load->view('public/partials/news_card', array('n' => $n)); ?>
      </div>

      <?php if ($paging['pages'] > 1): ?>
        <nav class="pap-pager" aria-label="Trang danh sách tin">
          <?php if ($paging['current'] > 1): ?>
            <a href="<?php echo $page_url($paging['current'] - 1); ?>" class="btn pap-btn-outline"><i class="bi bi-chevron-left"></i> Tin mới hơn</a>
          <?php endif; ?>
          <span class="pap-pager-info">Trang <?php echo $paging['current']; ?> / <?php echo $paging['pages']; ?></span>
          <?php if ($paging['current'] < $paging['pages']): ?>
            <a href="<?php echo $page_url($paging['current'] + 1); ?>" class="btn pap-btn-outline">Tin cũ hơn <i class="bi bi-chevron-right"></i></a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
