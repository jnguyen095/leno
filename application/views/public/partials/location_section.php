<?php
  // Liên hệ & đường đi: thông tin + các nút lớn (Gọi, Zalo, Chỉ đường, Facebook) và bản đồ.
?>
<section class="pap-section pap-section-soft" id="lien-he" aria-labelledby="location-title">
  <div class="container">
    <h2 id="location-title" class="pap-section-title text-center">Liên hệ &amp; đường đi</h2>
    <p class="pap-section-subtitle text-center">Gọi điện hoặc nhắn tin để hỏi giá, đặt sân và mọi thông tin khác.</p>
    <div class="row g-4 align-items-stretch mt-1">
      <div class="col-lg-5">
        <div class="pap-location-card">
          <ul class="pap-contact-list">
            <li>
              <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
              <span><small>Địa chỉ</small><a href="<?php echo htmlspecialchars($site_directions_url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($site_address); ?></a></span>
            </li>
            <?php if ($site_hours): ?>
              <li><i class="bi bi-clock-fill" aria-hidden="true"></i><span><small>Giờ mở cửa</small><?php echo htmlspecialchars($site_hours); ?></span></li>
            <?php endif; ?>
            <li>
              <i class="bi bi-telephone-fill" aria-hidden="true"></i>
              <span><small>Điện thoại</small><a href="<?php echo htmlspecialchars($site_phone_link); ?>" onclick="papTrack('click_call')"><?php echo htmlspecialchars($site_phone); ?></a></span>
            </li>
          </ul>
          <div class="pap-contact-buttons">
            <a href="<?php echo htmlspecialchars($site_phone_link); ?>" class="btn pap-btn-primary" onclick="papTrack('click_call')"><i class="bi bi-telephone-fill"></i> Gọi điện</a>
            <?php if ($site_zalo): ?>
              <a href="<?php echo htmlspecialchars($site_zalo); ?>" target="_blank" rel="noopener" class="btn pap-btn-outline" onclick="papTrack('click_zalo')"><i class="bi bi-chat-dots-fill"></i> Nhắn Zalo</a>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($site_directions_url); ?>" target="_blank" rel="noopener" class="btn pap-btn-outline" onclick="papTrack('click_map')"><i class="bi bi-signpost-2-fill"></i> Chỉ đường</a>
            <?php if ($site_facebook): ?>
              <a href="<?php echo htmlspecialchars($site_facebook); ?>" target="_blank" rel="noopener" class="btn pap-btn-outline" onclick="papTrack('click_facebook')"><i class="bi bi-facebook"></i> Facebook</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="pap-map-embed">
          <iframe src="<?php echo htmlspecialchars($site_map_embed_url); ?>" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade" title="Bản đồ đường đến <?php echo htmlspecialchars($site_name); ?>"></iframe>
        </div>
      </div>
    </div>
  </div>
</section>
