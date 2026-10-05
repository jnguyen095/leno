<nav class="pap-bottom-nav d-lg-none" aria-label="Liên hệ nhanh">
  <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $site_phone); ?>" class="pap-bottom-nav-item" onclick="papTrack('click_call')">
    <i class="bi bi-telephone-fill"></i>
    <span>Gọi</span>
  </a>
  <?php if ($site_zalo): ?>
    <a href="<?php echo htmlspecialchars($site_zalo); ?>" target="_blank" rel="noopener" class="pap-bottom-nav-item" onclick="papTrack('click_zalo')">
      <i class="bi bi-chat-dots-fill"></i>
      <span>Zalo</span>
    </a>
  <?php else: ?>
    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $site_phone); ?>" class="pap-bottom-nav-item" onclick="papTrack('click_zalo')">
      <i class="bi bi-chat-dots-fill"></i>
      <span>Zalo</span>
    </a>
  <?php endif; ?>
  <a href="#location" class="pap-bottom-nav-item pap-bottom-nav-highlight" onclick="papTrack('click_directions')">
    <i class="bi bi-geo-alt-fill"></i>
    <span>Chỉ đường</span>
  </a>
</nav>
