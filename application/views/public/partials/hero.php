<?php
  // Đầu trang chủ: banner + thẻ thông tin (tên, dịch vụ, giờ mở cửa, địa chỉ) và 2 nút chính Gọi / Chỉ đường —
  // khách mở trang là thấy ngay cách liên hệ, không phải cuộn tìm.
  // Banner: ảnh ở Quản trị → Banner trang chủ ($banners). Từ 2 ảnh trở lên chạy băng chuyền (tự chuyển, vuốt, mũi tên,
  // chấm chỉ số); 1 ảnh hiện tĩnh; chưa có ảnh nào thì dùng ảnh mặc định.
  $hero_default_alt = 'Khu vui chơi trẻ em, cà phê và đồ ăn vặt';
  $hero_slides = array();
  foreach ($banners as $b)
  {
    $hero_slides[] = array('src' => base_url('assets/'.$b['image']), 'alt' => $b['title'] ?: $hero_default_alt, 'link' => $b['link_url']);
  }
  if ( ! $hero_slides)
  {
    $hero_slides[] = array('src' => base_url('assets/img/nang_nghieng_banner.png'), 'alt' => $hero_default_alt, 'link' => NULL);
  }
  // Link trong website lưu dạng /tin-tuc.html → thêm địa chỉ gốc (website có thể chạy trong thư mục con, vd /leno/).
  $hero_href = function ($link) { return $link[0] === '/' ? rtrim(base_url(), '/').$link : $link; };
  $hero_img = function ($s, $i) {
    return '<img src="'.htmlspecialchars($s['src']).'" class="pap-hero-banner" width="2048" height="768" alt="'.htmlspecialchars($s['alt']).'"'
      .($i === 0 ? ' fetchpriority="high"' : ' loading="lazy"').'>';
  };
?>
<section class="pap-hero" aria-labelledby="hero-title">
  <?php if (count($hero_slides) === 1): $s = $hero_slides[0]; ?>
    <?php echo $s['link'] ? '<a href="'.htmlspecialchars($hero_href($s['link'])).'" onclick="papTrack(\'click_banner\', {banner: 1})">'.$hero_img($s, 0).'</a>' : $hero_img($s, 0); ?>
  <?php else: ?>
    <div id="papHeroCarousel" class="carousel slide pap-hero-carousel" data-pap-interval="<?php echo max(3, (int) $banner_seconds) * 1000; ?>" aria-roledescription="carousel" aria-label="Ảnh nổi bật">
      <div class="carousel-indicators">
        <?php foreach ($hero_slides as $i => $s): ?>
          <button type="button" data-bs-target="#papHeroCarousel" data-bs-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?> aria-label="Ảnh <?php echo $i + 1; ?>"></button>
        <?php endforeach; ?>
      </div>
      <div class="carousel-inner">
        <?php foreach ($hero_slides as $i => $s): ?>
          <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
            <?php echo $s['link'] ? '<a href="'.htmlspecialchars($hero_href($s['link'])).'" onclick="papTrack(\'click_banner\', {banner: '.($i + 1).'})">'.$hero_img($s, $i).'</a>' : $hero_img($s, $i); ?>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#papHeroCarousel" data-bs-slide="prev" aria-label="Ảnh trước">
        <span class="pap-carousel-arrow"><i class="bi bi-chevron-left"></i></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#papHeroCarousel" data-bs-slide="next" aria-label="Ảnh sau">
        <span class="pap-carousel-arrow"><i class="bi bi-chevron-right"></i></span>
      </button>
    </div>
  <?php endif; ?>
  <div class="container">
    <div class="pap-hero-card">
      <div class="pap-hero-text">
        <h1 id="hero-title" class="pap-hero-title">Nắng Nghiêng</h1>
        <p class="pap-hero-tagline">Khu vui chơi trẻ em · Cà phê &amp; ăn vặt</p>
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
