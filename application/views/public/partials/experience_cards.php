<?php
  // Các dịch vụ: điện thoại — mỗi dịch vụ một hàng (ảnh nhỏ bên trái, chữ bên phải) cho gọn, cả thẻ bấm được;
  // máy tính — 4 thẻ một hàng. Mỗi thẻ mở trang nội dung tương ứng (Quản trị → Trang nội dung).
  $services = array(
    array('page/khu-vui-choi', 'ng-khu-vui-choi.jpg', 'bi-balloon', 'Khu vui chơi trẻ em', 'Không gian vui chơi năng động, an toàn cho bé.', 'Giá vé · Ưu đãi', 'click_kids'),
    array('page/ca-phe-nuoc-uong', 'nn-ca-phe.jpg', 'bi-cup-hot', 'Cà phê & Nước uống', 'Thư giãn với nước uống và đồ ăn vặt.', 'Menu · Combo', 'click_cafe'),
    array('page/an-vat', 'nn-fast-food.jpg', 'bi-camera', 'Ăn vặt - Fast food', 'Lưu lại khoảnh khắc đáng nhớ cùng gia đình, bạn bè.', 'Khung ảnh · Bảng giá', 'click_photobooth'),
  );
?>
<section class="pap-section" id="dich-vu" aria-labelledby="services-title">
  <div class="container">
    <h2 id="services-title" class="pap-section-title text-center">Bạn muốn làm gì hôm nay?</h2>
    <p class="pap-section-subtitle text-center">Bấm vào từng mục để xem giá, hình ảnh và ưu đãi.</p>

    <div class="pap-services">
      <?php foreach ($services as $s): ?>
        <a class="pap-service" href="<?php echo site_url($s[0]); ?>" onclick="papTrack('<?php echo $s[6]; ?>')">
          <img src="<?php echo base_url('assets/img/public/'.$s[1]); ?>" alt="" class="pap-service-img" loading="lazy" width="800" height="600">
          <span class="pap-service-body">
            <span class="pap-service-title"><i class="bi <?php echo $s[2]; ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($s[3]); ?></span>
            <span class="pap-service-desc"><?php echo htmlspecialchars($s[4]); ?></span>
            <span class="pap-service-meta"><?php echo htmlspecialchars($s[5]); ?></span>
            <span class="pap-service-more">Xem chi tiết <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
