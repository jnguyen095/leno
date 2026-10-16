<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<?php if ( ! empty($site_ga4_id) && empty($current_user)): // Google Analytics 4 (Cài đặt → Website) — nhân viên đang đăng nhập không tính ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo rawurlencode($site_ga4_id); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', <?php echo json_encode($site_ga4_id); ?>);
</script>
<?php endif; ?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?php echo htmlspecialchars($seo['title']); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($seo['description']); ?>">
<link rel="canonical" href="<?php echo htmlspecialchars($seo['canonical']); ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($seo['title']); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($seo['description']); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($seo['canonical']); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars( ! empty($seo['image']) ? $seo['image'] : base_url('assets/public/images/hero.svg')); ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="<?php echo base_url('assets/img/logo_sm-removebg.png'); ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php if (isset($page)): // kiểu chữ / ảnh / bảng của nội dung soạn bằng CKEditor (Trang nội dung) ?>
<link href="https://cdn.jsdelivr.net/npm/ckeditor5@43.3.1/dist/browser/ckeditor5-content.css" rel="stylesheet">
<link href="<?php echo htmlspecialchars(Page_model::FONTS_URL); ?>" rel="stylesheet">
<?php endif; ?>
<link href="<?php echo asset_url('assets/public/css/public.css'); ?>" rel="stylesheet">

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
        <img src="<?php echo base_url('assets/img/nang-nghieng-icon.png'); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" width="150px">
      </a>
      <?php
        // Menu dùng chung (layouts/nav_items.php); mục đang xem được tô màu.
        $pap_nav = include APPPATH.'views/public/layouts/nav_items.php';
        $pap_current = isset($page['code']) ? 'page/'.$page['code'] : '';
      ?>
      <?php // Điện thoại: chữ "Menu" cạnh biểu tượng — dễ hiểu hơn biểu tượng ba gạch đứng một mình. ?>
      <button class="navbar-toggler pap-menu-btn" type="button" data-bs-toggle="collapse" data-bs-target="#papNav" aria-controls="papNav" aria-expanded="false" aria-label="Mở menu">
        <i class="bi bi-list"></i><span>Menu</span>
      </button>
      <div class="collapse navbar-collapse" id="papNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <?php foreach ($pap_nav as $item): $active = $item[0] === $pap_current; ?>
            <li class="nav-item<?php echo $item[0] === '' ? ' d-lg-none' : ''; ?>">
              <a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo $item[0] === '' ? base_url() : site_url($item[0]); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                <i class="bi <?php echo $item[1]; ?> d-lg-none"></i><?php echo htmlspecialchars($item[2]); ?>
              </a>
            </li>
          <?php endforeach; ?>
          <?php if ( ! empty($current_user)): // nhân viên đã đăng nhập: lối tắt vào hệ thống (khách không thấy) ?>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo site_url('me/'.($current_user['role'] === 'STOCKTAKER' ? 'stock/adjust' : 'dashboard')); ?>" title="Vào hệ thống quản lý">
                <i class="bi bi-person-circle"></i><?php echo htmlspecialchars($current_user['fullname']); ?>
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
            <a class="btn pap-btn-primary w-100" href="<?php echo base_url(); ?>#lien-he"><i class="bi bi-telephone-fill"></i> Liên hệ</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>

<main id="main-content">