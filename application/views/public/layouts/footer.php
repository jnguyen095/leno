</main>

<footer class="pap-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="pap-footer-brand">Leno</div>
        <p class="pap-footer-tagline">Vui chơi • Thể thao • Cà phê • Check-in</p>
        <div class="pap-footer-social">
          <?php if ($site_facebook): ?><a href="<?php echo htmlspecialchars($site_facebook); ?>" target="_blank" rel="noopener" aria-label="Facebook Leno"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if ($site_tiktok): ?><a href="<?php echo htmlspecialchars($site_tiktok); ?>" target="_blank" rel="noopener" aria-label="TikTok Leno"><i class="bi bi-tiktok"></i></a><?php endif; ?>
          <?php if ($site_zalo): ?><a href="<?php echo htmlspecialchars($site_zalo); ?>" target="_blank" rel="noopener" aria-label="Zalo Leno"><i class="bi bi-chat-dots-fill"></i></a><?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="pap-footer-heading">Khám phá</div>
        <ul class="pap-footer-links">
          <li><a href="#kids">Khu vui chơi</a></li>
          <li><a href="#cafe">Cà phê</a></li>
          <li><a href="#photobooth">Photobooth</a></li>
          <li><a href="#today">Khuyến mãi</a></li>
          <li><a href="#location">Liên hệ</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4">
        <div class="pap-footer-heading">Liên hệ</div>
        <address class="pap-footer-address">
          <div><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($site_address); ?></div>
          <div><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $site_phone); ?>" onclick="papTrack('click_call')"><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($site_phone); ?></a></div>
        </address>
      </div>
    </div>
    <hr class="pap-footer-divider">
    <div class="pap-footer-bottom">&copy; <?php echo date('Y'); ?> Leno. Tất cả các quyền được bảo lưu.</div>
  </div>
</footer>

<?php $this->load->view('public/layouts/mobile_bottom_nav'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url('assets/public/js/public.js'); ?>"></script>
<script>
  // GA4 — chỉ tải nếu có cấu hình PAP_GA4_ID (đặt trong public.js hoặc để trống để tắt hẳn).
  papInitAnalytics();
</script>
</body>
</html>
