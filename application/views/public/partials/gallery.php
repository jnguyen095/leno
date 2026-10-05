<?php if ( ! empty($gallery)): ?>
<?php $categories = array('kids' => 'Khu vui chơi', 'cafe' => 'Cà phê', 'photobooth' => 'Photobooth'); ?>
<section class="pap-section pap-section-alt" aria-labelledby="gallery-title">
  <div class="container">
    <h2 id="gallery-title" class="pap-section-title text-center">Góc nhỏ tại Leno</h2>

    <div class="pap-gallery-filters text-center" role="tablist" aria-label="Lọc thư viện ảnh theo khu vực">
      <button type="button" class="pap-filter-btn active" data-filter="all">Tất cả</button>
      <?php foreach ($categories as $key => $label): ?>
        <button type="button" class="pap-filter-btn" data-filter="<?php echo $key; ?>"><?php echo $label; ?></button>
      <?php endforeach; ?>
    </div>

    <div class="pap-gallery-grid">
      <?php foreach ($gallery as $g): ?>
        <a href="<?php echo base_url('assets/public/images/'.$g['image']); ?>"
           class="pap-gallery-item"
           data-category="<?php echo htmlspecialchars($g['category']); ?>"
           data-lightbox="1"
           aria-label="Xem lớn: <?php echo htmlspecialchars($g['title']); ?>">
          <img src="<?php echo base_url('assets/public/images/'.$g['image']); ?>" alt="<?php echo htmlspecialchars($g['title']); ?>" loading="lazy" width="600" height="600">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="pap-lightbox" id="papLightbox" role="dialog" aria-modal="true" aria-label="Xem ảnh lớn" hidden>
  <button type="button" class="pap-lightbox-close" id="papLightboxClose" aria-label="Đóng">&times;</button>
  <img src="" alt="" id="papLightboxImg">
</div>
<?php endif; ?>
