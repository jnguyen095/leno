<?php // Trang chính Quản trị: các chức năng chia nhóm, mỗi chức năng một thẻ to dễ bấm (cả trên điện thoại). ?>
<div class="admin-home">
  <div class="mb-3">
    <h4 class="fw-bold mb-1"><i class="bi bi-gear"></i> Quản trị</h4>
    <div class="text-muted small">Chọn chức năng cần quản lý.</div>
  </div>
  <?php foreach ($groups as $g): ?>
    <section class="admin-home-group">
      <h6 class="admin-home-title"><i class="bi <?php echo $g[1]; ?>"></i> <?php echo $g[0]; ?></h6>
      <div class="row g-2 g-md-3">
        <?php foreach ($g[2] as $it): ?>
          <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?php echo site_url($it[0]); ?>" class="admin-tile">
              <span class="admin-tile-icon"><i class="bi <?php echo $it[1]; ?>"></i></span>
              <span class="admin-tile-text"><b><?php echo $it[2]; ?></b><small><?php echo $it[3]; ?></small></span>
              <i class="bi bi-chevron-right admin-tile-go"></i>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>
