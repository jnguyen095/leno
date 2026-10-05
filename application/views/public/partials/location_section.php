<?php
  $maps_embed_url = $site_google_maps ?: ('https://www.google.com/maps?q='.rawurlencode($site_address).'&output=embed');
  $maps_directions_url = $site_google_maps ?: ('https://www.google.com/maps/search/?api=1&query='.rawurlencode($site_address));
  $phone_digits = preg_replace('/[^0-9+]/', '', $site_phone);
?>
<section class="pap-section" id="location" aria-labelledby="location-title">
  <div class="container">
    <h2 id="location-title" class="pap-section-title text-center">Tìm Leno</h2>
    <div class="row g-4 align-items-stretch mt-2">
      <div class="col-lg-5">
        <div class="pap-location-card">
          <p class="pap-location-address"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($site_address); ?></p>
          <p class="pap-location-phone"><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($site_phone); ?></p>
          <div class="pap-location-buttons">
            <a href="tel:<?php echo $phone_digits; ?>" class="btn pap-btn-primary" onclick="papTrack('click_call')"><i class="bi bi-telephone-fill"></i> Gọi ngay</a>
            <?php if ($site_zalo): ?>
              <a href="<?php echo htmlspecialchars($site_zalo); ?>" target="_blank" rel="noopener" class="btn pap-btn-outline" onclick="papTrack('click_zalo')"><i class="bi bi-chat-dots-fill"></i> Nhắn Zalo</a>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($maps_directions_url); ?>" target="_blank" rel="noopener" class="btn pap-btn-outline" onclick="papTrack('click_map')"><i class="bi bi-signpost-2-fill"></i> Chỉ đường</a>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="pap-map-embed">
          <iframe src="<?php echo htmlspecialchars($maps_embed_url); ?>" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bản đồ Leno"></iframe>
        </div>
      </div>
    </div>
  </div>
</section>
