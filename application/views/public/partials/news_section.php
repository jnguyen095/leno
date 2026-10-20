<?php
  // Trang chủ — "Tin mới" ngay dưới banner: 3 tin mới nhất (tin ghim trước). Điện thoại: trượt ngang từng thẻ;
  // máy tính: 3 cột. Không có tin thì ẩn cả khối. Biến: $news.
  if (empty($news)) return;
?>
<section class="pap-section pap-news-section" id="tin-moi" aria-labelledby="news-title">
  <div class="container">
    <div class="pap-news-head">
      <h2 id="news-title" class="pap-section-title mb-0"><i class="bi bi-megaphone-fill" aria-hidden="true"></i> Tin mới</h2>
      <a href="<?php echo site_url('tin-tuc'); ?>" class="pap-news-all">Xem tất cả <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
    </div>
    <div class="pap-news-strip">
      <?php foreach ($news as $n) $this->load->view('public/partials/news_card', array('n' => $n)); ?>
    </div>
  </div>
</section>
