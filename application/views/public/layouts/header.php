<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?php echo htmlspecialchars($seo['title']); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($seo['description']); ?>">
<link rel="canonical" href="<?php echo htmlspecialchars($seo['canonical']); ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($seo['title']); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($seo['description']); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($seo['canonical']); ?>">
<meta property="og:image" content="<?php echo base_url('assets/public/images/hero.svg'); ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="<?php echo base_url('assets/img/logo_sm-removebg.png'); ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo base_url('assets/public/css/public.css'); ?>" rel="stylesheet">

<script type="application/ld+json">
<?php echo json_encode(array(
    '@context'  => 'https://schema.org',
    '@type'     => 'LocalBusiness',
    'name'      => $site_name,
    'telephone' => $site_phone,
    'address'   => array(
        '@type'           => 'PostalAddress',
        'streetAddress'   => $site_address,
        'addressLocality' => 'Buôn Ma Thuột',
        'addressRegion'   => 'Đắk Lắk',
        'addressCountry'  => 'VN',
    ),
    'url' => base_url(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>
</head>
<body>

<a href="#main-content" class="pap-skip-link">Bỏ qua đến nội dung chính</a>

<header class="pap-header">
  <nav class="navbar navbar-expand-lg pap-navbar" aria-label="Điều hướng chính">
    <div class="container">
      <a class="navbar-brand pap-brand" href="<?php echo site_url(); ?>">
        <img src="<?php echo base_url('assets/img/logo_sm-removebg_header.png'); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" width="100px">
        <span class="pap-brand-name">Leno</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#papNav" aria-controls="papNav" aria-expanded="false" aria-label="Mở menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="collapse navbar-collapse" id="papNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <li class="nav-item"><a class="nav-link" href="<?php echo site_url(); ?>">Trang chủ</a></li>
          <li class="nav-item"><a class="nav-link" href="#kids">Khu vui chơi</a></li>
          <li class="nav-item"><a class="nav-link" href="#cafe">Cà phê &amp; Photobooth</a></li>
          <li class="nav-item"><a class="nav-link" href="#today">Khuyến mãi</a></li>
          <li class="nav-item"><a class="nav-link" href="#location">Liên hệ</a></li>
          <li class="nav-item">
            <?php if ( ! empty($current_user)): ?>
              <?php
                $pap_home = 'dashboard';
                if ($current_user['role'] === 'STOCKTAKER') $pap_home = 'stock/adjust';
              ?>
              <a class="nav-link" href="<?php echo site_url('me/'.$pap_home); ?>" title="Vào hệ thống quản lý"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user['fullname']); ?></a>
            <?php else: ?>
              <a class="nav-link" href="<?php echo site_url('login'); ?>" title="Đăng nhập nhân viên"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
            <?php endif; ?>
          </li>
          <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
            <a class="btn pap-btn-primary w-100" href="#location" onclick="papTrack('click_contact')">Liên hệ</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>

<main id="main-content">