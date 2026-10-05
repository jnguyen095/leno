<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Chương trình đã kết thúc - Leno</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{
    margin:0; min-height:100vh;
    background: radial-gradient(circle at 50% 0%, #3a2a63 0%, #1c1440 55%, #100b28 100%);
    font-size:18px; color:#3a1f00;
    padding: 1.75rem 1rem;
    display:flex; align-items:center;
  }
  .tt-wrap{ max-width:480px; margin:0 auto; width:100%; }
  .tt-card{
    background:#fffaf0; border-radius:28px;
    box-shadow:0 12px 34px rgba(0,0,0,.4);
    padding:2rem 1.35rem; text-align:center;
  }
  .tt-icon{ width:170px; max-width:65%; display:block; margin:0 auto .5rem; }
  .tt-title{ font-size:1.6rem; font-weight:800; color:#c1272d; line-height:1.3; }
  .tt-gift{
    font-size:1.2rem; font-weight:800; color:#c1272d;
    background:#fff3cd; border:2px dashed #e8b34a; border-radius:16px;
    padding:.9rem .75rem; margin:1.25rem 0; line-height:1.5;
  }
  .tt-note{ font-size:1.1rem; color:#7a2e00; margin-top:.75rem; line-height:1.6; }
</style>
</head>
<body>
  <div class="tt-wrap">
    <div class="tt-card">
      <img src="<?php echo base_url('assets/img/trung-thu-icon-removebg.png'); ?>" alt="Trung thu" class="tt-icon">
      <div class="tt-title">🌕 Chương trình đã kết thúc!</div>
      <div class="tt-gift">🎁 Đăng ký nhận quà Trung Thu đã đóng.</div>
      <div class="tt-note">
        Cảm ơn Ba/Mẹ và các bé đã tham gia cùng Leno! ❤️<br>
        Hẹn gặp lại gia đình trong những chương trình tiếp theo! 🎉
      </div>
    </div>
  </div>
</body>
</html>
