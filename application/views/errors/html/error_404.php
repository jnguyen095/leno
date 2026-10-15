<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Trang "Không tìm thấy" thân thiện — dùng cho mọi lỗi 404: URL không có route, show_404() trong controller
 * (vd trang nội dung /page/<code>.html đã tắt / không có, đơn không tồn tại...).
 * Chạy cả khi chưa có controller (lỗi ngay ở bước định tuyến) nên KHÔNG dùng helper / model của CI —
 * chỉ config_item() và $_SERVER.
 */
$base = rtrim((string) config_item('base_url'), '/').'/';
$base_path = rtrim((string) parse_url($base, PHP_URL_PATH), '/');
$path = (string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
$uri = ltrim($base_path !== '' && strpos($path, $base_path) === 0 ? substr($path, strlen($base_path)) : $path, '/');

// API: trả JSON thay vì trang HTML.
if (strpos($uri, 'api/') === 0)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => array('code' => 'NOT_FOUND', 'message' => 'Không tìm thấy địa chỉ yêu cầu.')), JSON_UNESCAPED_UNICODE);
    return;
}

$is_internal = $uri === 'me' || strpos($uri, 'me/') === 0;
$is_content_page = strpos($uri, 'page/') === 0;
$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$suffix = (string) config_item('url_suffix');
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Không tìm thấy trang – Nắng Nghiêng</title>
<link rel="icon" href="<?php echo $h($base.'assets/img/logo_sm-removebg.png'); ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root{ --nf-primary:#0190db; --nf-primary-dark:#016fa8; --nf-dark:#1c2b39; --nf-text:#5c6b78; --nf-light:#f7f9fc; }
  *{ box-sizing:border-box; }
  body{ margin:0; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center;
        padding:24px 16px; background:linear-gradient(180deg, #eaf6fd 0%, var(--nf-light) 55%); color:var(--nf-dark);
        font-family:'Segoe UI', Roboto, Arial, sans-serif; text-align:center; }
  .nf-logo{ height:44px; margin-bottom:28px; }
  .nf-card{ background:#fff; border-radius:24px; box-shadow:0 12px 32px rgba(28,43,57,.08); padding:40px 28px 32px; width:100%; max-width:520px; }
  .nf-code{ font-size:88px; line-height:1; font-weight:900; letter-spacing:4px; margin:0;
            background:linear-gradient(135deg, var(--nf-primary), #ff9800); -webkit-background-clip:text; background-clip:text; color:transparent; }
  .nf-icon{ font-size:44px; color:var(--nf-primary); margin-bottom:8px; display:inline-block; animation:nf-bob 2.4s ease-in-out infinite; }
  @keyframes nf-bob{ 0%,100%{ transform:translateY(0) rotate(-6deg); } 50%{ transform:translateY(-8px) rotate(6deg); } }
  @media (prefers-reduced-motion: reduce){ .nf-icon{ animation:none; } }
  h1{ font-size:24px; font-weight:800; margin:16px 0 8px; }
  p{ color:var(--nf-text); font-size:16px; line-height:1.6; margin:0 auto; max-width:400px; }
  .nf-path{ display:inline-block; max-width:100%; margin-top:14px; padding:4px 12px; border-radius:999px; background:var(--nf-light);
            color:var(--nf-text); font:13px/1.6 Consolas, Monaco, monospace; overflow-wrap:anywhere; }
  .nf-actions{ display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:28px; }
  .nf-btn{ display:inline-flex; align-items:center; gap:8px; min-height:46px; padding:0 22px; border-radius:999px; font-weight:700;
           font-size:15px; text-decoration:none; border:2px solid var(--nf-primary); cursor:pointer; font-family:inherit; }
  .nf-btn-primary{ background:var(--nf-primary); color:#fff; }
  .nf-btn-primary:hover, .nf-btn-primary:focus-visible{ background:var(--nf-primary-dark); border-color:var(--nf-primary-dark); }
  .nf-btn-outline{ background:#fff; color:var(--nf-primary); }
  .nf-btn-outline:hover, .nf-btn-outline:focus-visible{ background:var(--nf-primary); color:#fff; }
  .nf-btn:focus-visible{ outline:3px solid #ff9800; outline-offset:2px; }
  .nf-foot{ margin-top:24px; font-size:13px; color:#8a97a3; }
  @media (max-width: 480px){
    .nf-card{ padding:32px 20px 24px; }
    .nf-code{ font-size:72px; }
    .nf-actions{ flex-direction:column; }
    .nf-btn{ justify-content:center; width:100%; }
  }
</style>
</head>
<body>
  <a href="<?php echo $h($base); ?>"><img class="nf-logo" src="<?php echo $h($base.'assets/img/nang-nghieng-icon.png'); ?>" alt="Nắng Nghiêng"></a>
  <main class="nf-card">
    <span class="nf-icon" aria-hidden="true"><i class="bi bi-signpost-split"></i></span>
    <p class="nf-code" aria-hidden="true">404</p>
    <h1>Ôi, không tìm thấy trang này</h1>
    <p>
      <?php if ($is_content_page): ?>
        Trang bạn tìm có thể đã được gỡ xuống hoặc đường dẫn đã thay đổi.
      <?php elseif ($is_internal): ?>
        Trang quản lý này không tồn tại hoặc dữ liệu đã bị xoá.
      <?php else: ?>
        Đường dẫn có thể bị gõ nhầm, hoặc trang đã được chuyển sang địa chỉ khác.
      <?php endif; ?>
    </p>
    <?php if ($uri !== ''): ?><div class="nf-path">/<?php echo $h($uri); ?></div><?php endif; ?>

    <div class="nf-actions">
      <?php if ($is_internal): ?>
        <a class="nf-btn nf-btn-primary" href="<?php echo $h($base.'me/dashboard'.$suffix); ?>"><i class="bi bi-speedometer2"></i> Về trang quản lý</a>
      <?php else: ?>
        <a class="nf-btn nf-btn-primary" href="<?php echo $h($base); ?>"><i class="bi bi-house-door"></i> Về trang chủ</a>
      <?php endif; ?>
      <button type="button" class="nf-btn nf-btn-outline" id="nfBack" hidden><i class="bi bi-arrow-left"></i> Quay lại</button>
      <?php if ( ! $is_internal): ?>
        <a class="nf-btn nf-btn-outline" href="<?php echo $h($base.'#location'); ?>"><i class="bi bi-telephone"></i> Liên hệ</a>
      <?php endif; ?>
    </div>
  </main>
  <div class="nf-foot">© <?php echo date('Y'); ?> Nắng Nghiêng</div>
<script>
  // "Quay lại" chỉ hiện khi có trang trước trong cùng website.
  (function(){
    var b = document.getElementById('nfBack');
    try { if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) b.hidden = false; } catch (e) {}
    b.addEventListener('click', function(){ history.back(); });
  })();
</script>
</body>
</html>
