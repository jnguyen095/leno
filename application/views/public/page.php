<?php // Trang nội dung website (/page/<code>.html) — nội dung soạn bằng CKEditor ở Quản trị → Trang nội dung. ?>
<?php if ($is_preview): ?>
  <div class="pap-page-preview">
    <i class="bi bi-eye-slash"></i> Trang đang <b>tắt</b> — chỉ quản trị viên xem được.
    <a href="<?php echo site_url('me/pages/'.$page['id'].'/edit'); ?>">Sửa trang</a>
  </div>
<?php endif; ?>
<section class="pap-section">
  <div class="container pap-page-wrap">
    <h1 class="pap-page-title"><?php echo htmlspecialchars($page['title']); ?></h1>
    <article class="pap-page-content ck-content">
      <?php echo $content; ?>
    </article>
  </div>
</section>
