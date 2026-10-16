<?php // Thanh liên hệ nhanh dưới cùng (điện thoại): Gọi · Zalo (nếu có) · Chỉ đường — mỗi nút một chạm là xong. ?>
<nav class="pap-bottom-nav d-lg-none" aria-label="Liên hệ nhanh">
  <a href="<?php echo htmlspecialchars($site_phone_link); ?>" class="pap-bottom-nav-item pap-bottom-nav-highlight" onclick="papTrack('click_call')">
    <i class="bi bi-telephone-fill"></i>
    <span>Gọi ngay</span>
  </a>
  <?php if ($site_zalo): ?>
    <a href="<?php echo htmlspecialchars($site_zalo); ?>" target="_blank" rel="noopener" class="pap-bottom-nav-item" onclick="papTrack('click_zalo')">
      <i class="bi bi-chat-dots-fill"></i>
      <span>Nhắn Zalo</span>
    </a>
  <?php endif; ?>
  <a href="<?php echo htmlspecialchars($site_directions_url); ?>" target="_blank" rel="noopener" class="pap-bottom-nav-item" onclick="papTrack('click_directions')">
    <i class="bi bi-signpost-2-fill"></i>
    <span>Chỉ đường</span>
  </a>
</nav>
