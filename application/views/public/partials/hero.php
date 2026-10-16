<?php
  // Đầu trang chủ: ảnh banner + thẻ thông tin (tên, dịch vụ, giờ mở cửa, địa chỉ) và 2 nút chính Gọi / Chỉ đường —
  // khách mở trang là thấy ngay cách liên hệ, không phải cuộn tìm.
?>
<section class="pap-hero" aria-labelledby="hero-title">
  <img src="<?php echo base_url('assets/img/public/pap-banner.png'); ?>" class="pap-hero-banner" width="2048" height="768" fetchpriority="high"
       alt="Khu vui chơi trẻ em, cà phê và đồ ăn vặt, photobooth và sân pickleball">
  <div class="container">
    <div class="pap-hero-card">
      <div class="pap-hero-text">
        <h1 id="hero-title" class="pap-hero-title">Pick Angle Park</h1>
        <p class="pap-hero-tagline">Khu vui chơi trẻ em · Cà phê &amp; ăn vặt · Photobooth · Pickleball</p>
        <ul class="pap-hero-info">
          <?php if ($site_hours): ?>
            <li><i class="bi bi-clock-fill" aria-hidden="true"></i><span><b>Mở cửa:</b> <?php echo htmlspecialchars($site_hours); ?></span></li>
          <?php endif; ?>
          <li><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><span><?php echo htmlspecialchars($site_address); ?></span></li>
        </ul>
      </div>
      <div class="pap-hero-actions">
        <a href="<?php echo htmlspecialchars($site_phone_link); ?>" class="btn pap-btn-primary pap-btn-lg" onclick="papTrack('click_call')">
          <i class="bi bi-telephone-fill"></i> Gọi <?php echo htmlspecialchars($site_phone); ?>
        </a>
        <a href="<?php echo htmlspecialchars($site_directions_url); ?>" target="_blank" rel="noopener" class="btn pap-btn-outline pap-btn-lg" onclick="papTrack('click_map')">
          <i class="bi bi-signpost-2-fill"></i> Chỉ đường
        </a>
      </div>
    </div>
  </div>
</section>
