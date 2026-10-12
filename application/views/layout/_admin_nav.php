<?php
  // Menu các trang quản trị. Máy tính: cột trái dính khi cuộn, chia nhóm. Điện thoại: dãy nút to trượt ngang,
  // mục đang mở luôn hiện. Biến: $admin_groups (admin_menu_groups()), $admin_active_url.
?>
<aside class="admin-nav" aria-label="Menu quản trị">
  <a href="<?php echo site_url('me/admin'); ?>" class="admin-nav-home <?php echo $admin_active_url === 'me/admin' ? 'active' : ''; ?>">
    <i class="bi bi-grid-1x2"></i> <span>Tổng quan quản trị</span>
  </a>
  <?php foreach ($admin_groups as $g): ?>
    <div class="admin-nav-group">
      <div class="admin-nav-title"><?php echo $g[0]; ?></div>
      <?php foreach ($g[2] as $it): ?>
        <a href="<?php echo site_url($it[0]); ?>" class="admin-nav-link <?php echo $admin_active_url === $it[0] ? 'active' : ''; ?>"
           <?php echo $admin_active_url === $it[0] ? 'aria-current="page"' : ''; ?>>
          <i class="bi <?php echo $it[1]; ?>"></i> <span><?php echo $it[2]; ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</aside>
<script>
// Điện thoại: dãy nút trượt ngang -> cuộn để mục đang mở nằm trong tầm nhìn.
(function(){
  var nav = document.currentScript.previousElementSibling, on = nav.querySelector('.active');
  if (on && nav.scrollWidth > nav.clientWidth) nav.scrollLeft = on.offsetLeft - 16;
  // Dính ngay dưới thanh điều hướng (cao khác nhau trên máy tính / điện thoại).
  var bar = document.querySelector('.navbar.sticky-top');
  function fit(){
    var h = bar && getComputedStyle(bar).display !== 'none' ? bar.offsetHeight : 0;
    nav.style.top = h + 'px';
    if (window.innerWidth >= 992) nav.style.maxHeight = 'calc(100vh - ' + h + 'px)'; else nav.style.maxHeight = '';
  }
  fit(); window.addEventListener('resize', fit);
})();
</script>
