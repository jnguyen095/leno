<?php
  // Trang chủ: đầu trang (banner + Gọi / Chỉ đường) → các dịch vụ → liên hệ & bản đồ.
  // Các khối cũ còn file trong partials/ (today_at_park, kids_section, cafe_section, photobooth_price, gallery, cta_section)
  // — bật lại bằng cách thêm dòng load->view tương ứng.
  $this->load->view('public/partials/hero');
  $this->load->view('public/partials/news_section', array('news' => $news));
  $this->load->view('public/partials/experience_cards');
  // $this->load->view('public/partials/today_at_park', array('promotions' => $promotions));
  $this->load->view('public/partials/location_section');
?>
