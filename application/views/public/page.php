<?php
  // Trang nội dung (/page/<code>.html) hoặc một tin (/tin-tuc/<code>.html) — nội dung soạn bằng CKEditor.
  // Tin tức thêm: ngày đăng, link "Tất cả tin" và 3 tin khác ở cuối. Biến: $page, $content, $banner, $is_preview, $more_news.
  $is_news = $page['type'] === 'NEWS';
?>
<?php if ($is_preview): ?>
  <div class="pap-page-preview">
    <i class="bi bi-eye-slash"></i>
    <?php if ((int) $page['status'] && Page_model::is_scheduled($page)): ?>
      Tin <b>hẹn giờ</b> — tự hiện cho khách lúc <?php echo date('H:i d/m/Y', strtotime($page['published_at'])); ?>. Hiện chỉ quản trị viên xem được.
    <?php else: ?>
      Bài đang <b>tắt</b> — chỉ quản trị viên xem được.
    <?php endif; ?>
    <a href="<?php echo site_url(($is_news ? 'me/news/' : 'me/pages/').$page['id'].'/edit'); ?>">Sửa</a>
  </div>
<?php endif; ?>
<?php if ($banner): ?>
  <div class="pap-page-banner">
    <img src="<?php echo htmlspecialchars($banner); ?>" alt="<?php echo htmlspecialchars($page['title']); ?>" fetchpriority="high">
  </div>
<?php endif; ?>
<section class="pap-section<?php echo $banner ? ' pap-page-has-banner' : ''; ?>">
  <div class="container pap-page-wrap">
    <?php if ($is_news): ?>
      <a href="<?php echo site_url('tin-tuc'); ?>" class="pap-back-link"><i class="bi bi-arrow-left"></i> Tất cả tin</a>
    <?php endif; ?>
    <h1 class="pap-page-title"><?php echo htmlspecialchars($page['title']); ?></h1>
    <?php if ($is_news && $page['published_at']): ?>
      <div class="pap-news-date pap-news-date-lg"><i class="bi bi-calendar3" aria-hidden="true"></i> Đăng ngày <?php echo date('d/m/Y', strtotime($page['published_at'])); ?></div>
    <?php endif; ?>
    <article class="pap-page-content ck-content">
      <?php echo $content; ?>
    </article>
  </div>
</section>
<?php if ( ! empty($more_news)): ?>
  <section class="pap-section pap-section-soft" aria-labelledby="more-news-title">
    <div class="container">
      <div class="pap-news-head">
        <h2 id="more-news-title" class="pap-section-title mb-0">Tin khác</h2>
        <a href="<?php echo site_url('tin-tuc'); ?>" class="pap-news-all">Xem tất cả <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
      </div>
      <div class="pap-news-grid">
        <?php foreach ($more_news as $n) $this->load->view('public/partials/news_card', array('n' => $n)); ?>
      </div>
    </div>
  </section>
<?php endif; ?>
