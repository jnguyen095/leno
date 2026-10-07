<?php
/**
 * Trang tải ứng dụng Android (.apk) cho máy POS.
 * Chép file .apk vào thư mục này (FTP / File Manager trên hosting) là nó tự hiện trong danh sách,
 * mới nhất ở trên cùng. Trang độc lập, không đi qua CodeIgniter.
 */
$dir = __DIR__;
$files = array();
foreach (glob($dir.'/*.apk') ?: array() as $path)
{
    $files[] = array(
        'name'  => basename($path),
        'size'  => filesize($path),
        'mtime' => filemtime($path),
    );
}
usort($files, function ($a, $b) { return $b['mtime'] - $a['mtime']; });

function human_size($bytes)
{
    $units = array('B', 'KB', 'MB', 'GB');
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
    return number_format($bytes, $i ? 1 : 0, ',', '.').' '.$units[$i];
}
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Tải ứng dụng — Leno</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{ background:#f5f6f8; }
  .apk-card{ border:0; border-radius:1rem; box-shadow:0 1px 4px rgba(0,0,0,.08); }
  .apk-icon{ width:48px; height:48px; border-radius:12px; background:#e8f5e9; color:#2e7d32; display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex:0 0 auto; }
  .apk-name{ font-weight:600; word-break:break-all; }
  .badge-latest{ background:#0d6efd; }
</style>
</head>
<body>
<div class="container py-4" style="max-width:640px;">
  <h4 class="fw-bold mb-1"><i class="bi bi-android2 text-success"></i> Tải ứng dụng</h4>
  <p class="text-muted small mb-4">Bấm <b>Tải về</b> trên máy POS rồi mở file vừa tải để cài đặt.</p>

  <?php if ( ! $files): ?>
    <div class="alert alert-secondary">Chưa có file cài đặt nào. Chép file <code>.apk</code> vào thư mục <code>downloads/</code> trên máy chủ.</div>
  <?php endif; ?>

  <?php foreach ($files as $i => $f): ?>
    <div class="card apk-card mb-3">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="apk-icon"><i class="bi bi-android2"></i></div>
        <div class="flex-grow-1" style="min-width:0;">
          <div class="apk-name"><?php echo htmlspecialchars($f['name']); ?>
            <?php if ($i === 0 && count($files) > 1): ?><span class="badge badge-latest ms-1">Mới nhất</span><?php endif; ?>
          </div>
          <div class="small text-muted"><?php echo human_size($f['size']); ?> · cập nhật <?php echo date('d/m/Y H:i', $f['mtime']); ?></div>
        </div>
        <a class="btn btn-success flex-shrink-0" href="<?php echo rawurlencode($f['name']); ?>" download>
          <i class="bi bi-download"></i> Tải về
        </a>
      </div>
    </div>
  <?php endforeach; ?>

  <details class="mt-4 small text-muted">
    <summary class="fw-semibold">Cách cài đặt trên máy POS (Android)</summary>
    <ol class="mt-2 ps-3">
      <li>Bấm <b>Tải về</b>, đợi tải xong.</li>
      <li>Mở file trong thông báo tải xuống (hoặc ứng dụng <i>Tệp / Files</i> → <i>Tải xuống</i>).</li>
      <li>Nếu máy hỏi, cho phép <b>"Cài đặt ứng dụng không rõ nguồn gốc"</b> cho trình duyệt đang dùng, rồi bấm <b>Cài đặt</b>.</li>
      <li>Cập nhật phiên bản mới: tải file mới nhất và cài đè lên — dữ liệu ứng dụng vẫn giữ nguyên.</li>
    </ol>
  </details>
</div>
</body>
</html>
