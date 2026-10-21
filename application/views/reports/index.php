<?php // Báo cáo — danh sách báo cáo người dùng được xem (chỉ hiện khi có từ 2 báo cáo trở lên). Biến: $reports. ?>
<div class="container-xl py-3 py-md-4">
  <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line"></i> Báo cáo</h4>
  <div class="text-muted small mb-3">Chọn báo cáo cần xem.</div>
  <div class="row g-3">
    <?php foreach ($reports as $r): ?>
      <div class="col-sm-6 col-lg-4">
        <a href="<?php echo site_url('me/reports/'.$r[0]); ?>" class="rp-card">
          <span class="rp-card-icon"><i class="bi <?php echo $r[1]; ?>"></i></span>
          <span><b><?php echo htmlspecialchars($r[2]); ?></b><small><?php echo htmlspecialchars($r[3]); ?></small></span>
          <i class="bi bi-chevron-right ms-auto text-muted"></i>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>
