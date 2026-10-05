<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Đăng ký nhận quà Trung Thu - Leno</title>
<meta property="og:type" content="website">
<meta property="og:title" content="🌕 Đêm Hội Trăng Rằm — Đăng ký nhận quà Trung Thu miễn phí">
<meta property="og:description" content="Cùng bé vui Trung Thu tại Leno! Đăng ký nhận quà miễn phí — <?php echo $event_label; ?>.">
<meta property="og:image" content="<?php echo $og_image_url; ?>">
<meta property="og:url" content="<?php echo $canonical_url; ?>">
<meta name="twitter:card" content="summary_large_image">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{
    margin:0; min-height:100vh;
    background: radial-gradient(circle at 50% 0%, #3a2a63 0%, #1c1440 55%, #100b28 100%);
    font-size:18px; color:#3a1f00;
    padding: 1.75rem 1rem;
  }
  .tt-wrap{ max-width:480px; margin:0 auto; }
  .tt-card{
    background:#fffaf0; border-radius:28px;
    box-shadow:0 12px 34px rgba(0,0,0,.4);
    padding:1.5rem 1.35rem 2rem;
  }
  .tt-icon{ width:170px; max-width:65%; display:block; margin:0 auto .25rem; }
  .tt-title{ font-size:1.7rem; font-weight:800; color:#c1272d; text-align:center; line-height:1.3; }
  .tt-subtitle{ font-size:1.2rem; font-weight:700; text-align:center; color:#7a2e00; margin-top:.35rem; }
  .tt-gift{
    font-size:1.3rem; font-weight:800; text-align:center; color:#c1272d;
    background:#fff3cd; border:2px dashed #e8b34a; border-radius:16px;
    padding:.9rem .75rem; margin:1.1rem 0; line-height:1.4;
  }
  .tt-time{ font-size:1.15rem; text-align:center; font-weight:700; color:#3a1f00; margin-bottom:1.25rem; }
  .tt-label{ font-size:1.15rem; font-weight:700; margin-bottom:.4rem; }
  .form-control{ font-size:1.2rem; padding:.85rem 1rem; border-radius:14px; border:2px solid #e8d9c3; }
  .form-control:focus{ border-color:#c1272d; box-shadow:0 0 0 .2rem rgba(193,39,45,.15); }
  .btn-tt{
    font-size:1.35rem; font-weight:800; padding:1rem; border-radius:16px;
    background:#c1272d; border:none; color:#fff; width:100%;
    box-shadow:0 6px 16px rgba(193,39,45,.4);
  }
  .btn-tt:active{ background:#a01f24; }
  .tt-alert{ font-size:1.05rem; border-radius:12px; }
</style>
</head>
<body>
  <div class="tt-wrap">
    <div class="tt-card">
      <img src="<?php echo base_url('assets/img/trung-thu-icon-removebg.png'); ?>" alt="Trung thu" class="tt-icon">
      <div class="tt-title">🌕 ĐÊM HỘI TRĂNG RẰM 🎉</div>
      <div class="tt-subtitle">Cùng bé vui Trung Thu tại<br>
       <b>Leno</b>
      </div>

      <div class="tt-gift">🎁 ĐĂNG KÝ NHẬN QUÀ TRUNG THU<br>HOÀN TOÀN MIỄN PHÍ!</div>
      <div class="tt-time">🗓 <?php echo $event_label; ?></div>

      <?php if ($error): ?>
        <div class="alert alert-danger tt-alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <?php echo form_open(current_url()); ?>
        <div class="mb-3">
          <label class="tt-label form-label">Số điện thoại</label>
          <input type="tel" name="phone" class="form-control" placeholder="VD: 0901234567" required inputmode="numeric" value="<?php echo htmlspecialchars($old['phone']); ?>">
        </div>
        <div class="mb-3">
          <label class="tt-label form-label">Tên Ba/Mẹ</label>
          <input type="text" name="parent_name" class="form-control" placeholder="Nhập tên Ba/Mẹ" required value="<?php echo htmlspecialchars($old['parent_name']); ?>">
        </div>
        <div class="mb-4">
          <label class="tt-label form-label">Số lượng bé đăng ký</label>
          <input type="number" name="kid_count" class="form-control" min="1" max="<?php echo $max_kids; ?>" placeholder="VD: 2" required value="<?php echo (int) $old['kid_count']; ?>">
          <div class="form-text">Tối đa <?php echo $max_kids; ?> bé mỗi lượt đăng ký.</div>
        </div>
        <button type="submit" class="btn-tt">🎁 Đăng ký ngay</button>
      <?php echo form_close(); ?>
    </div>
  </div>
</body>
</html>
