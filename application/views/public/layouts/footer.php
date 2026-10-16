</main>

<footer class="pap-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="pap-footer-brand">Pick Angel Park</div>
        <p class="pap-footer-tagline">Vui chơi • Thể thao • Cà phê • Check-in</p>
        <div class="pap-footer-social">
          <?php if ($site_facebook): ?><a href="<?php echo htmlspecialchars($site_facebook); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if ($site_tiktok): ?><a href="<?php echo htmlspecialchars($site_tiktok); ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a><?php endif; ?>
          <?php if ($site_zalo): ?><a href="<?php echo htmlspecialchars($site_zalo); ?>" target="_blank" rel="noopener" aria-label="Zalo"><i class="bi bi-chat-dots-fill"></i></a><?php endif; ?>
        </div>
      </div>
      <div class="col-sm-6 col-md-4">
        <div class="pap-footer-heading">Khám phá</div>
        <ul class="pap-footer-links">
          <?php foreach (include APPPATH.'views/public/layouts/nav_items.php' as $item): if ($item[0] === '') continue; ?>
            <li><a href="<?php echo site_url($item[0]); ?>"><?php echo htmlspecialchars($item[2]); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-sm-6 col-md-4">
        <div class="pap-footer-heading">Liên hệ</div>
        <address class="pap-footer-address">
          <div><i class="bi bi-geo-alt-fill"></i> <a href="<?php echo htmlspecialchars($site_directions_url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($site_address); ?></a></div>
          <?php if ($site_hours): ?><div><i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($site_hours); ?></div><?php endif; ?>
          <div><i class="bi bi-telephone-fill"></i> <a href="<?php echo htmlspecialchars($site_phone_link); ?>" onclick="papTrack('click_call')"><?php echo htmlspecialchars($site_phone); ?></a></div>
        </address>
      </div>
    </div>
    <hr class="pap-footer-divider">
    <div class="pap-footer-bottom">&copy; <?php echo date('Y'); ?> Pick Angel Park. Tất cả các quyền được bảo lưu.</div>
  </div>
</footer>

<?php $this->load->view('public/layouts/mobile_bottom_nav'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset_url('assets/public/js/public.js'); ?>"></script>
<script>
  // GA4 — chỉ tải nếu có cấu hình PAP_GA4_ID (đặt trong public.js hoặc để trống để tắt hẳn).
  papInitAnalytics();
</script>
</body>
</html>
