<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Đăng ký thành công - Leno</title>
<meta property="og:type" content="website">
<meta property="og:title" content="🌕 <?php echo htmlspecialchars($parent_name); ?> vừa đăng ký nhận quà Trung Thu tại Leno!">
<meta property="og:description" content="Đêm Hội Trăng Rằm — nhận quà miễn phí cho bé lúc <?php echo $event_label; ?>. Đăng ký ngay cho bé nhà bạn!">
<meta property="og:image" content="<?php echo $og_image_url; ?>">
<meta property="og:url" content="<?php echo $share_url; ?>">
<meta name="twitter:card" content="summary_large_image">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
  .tt-check{ font-size:3rem; margin-bottom:.25rem; }
  .tt-title{ font-size:1.7rem; font-weight:800; color:#c1272d; line-height:1.3; }
  .tt-msg{ font-size:1.2rem; margin-top:1rem; line-height:1.5; }
  .tt-gift{
    font-size:1.25rem; font-weight:800; color:#c1272d;
    background:#fff3cd; border:2px dashed #e8b34a; border-radius:16px;
    padding:.9rem .75rem; margin:1.25rem 0; line-height:1.5;
  }
  .tt-note{ font-size:1.05rem; color:#7a2e00; margin-top:.5rem; }
  .btn-tt-outline{
    font-size:1.1rem; font-weight:700; padding:.8rem 1rem; border-radius:14px;
    border:2px solid #c1272d; color:#c1272d; background:#fff; display:inline-block; margin-top:1.5rem;
    text-decoration:none;
  }
  .btn-tt-outline:active{ background:#fff3cd; }
  .tt-share{ margin-top:1.5rem; padding-top:1.25rem; border-top:1px dashed #e8d9c3; }
  .tt-share-label{ font-size:1.05rem; font-weight:700; color:#7a2e00; margin-bottom:.75rem; }
  .tt-share-row{ display:flex; gap:.5rem; flex-wrap:wrap; justify-content:center; margin-bottom:.75rem; }
  .btn-share{
    font-size:1.02rem; font-weight:700; padding:.65rem 1rem; border-radius:12px;
    border:none; color:#fff; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem;
    flex:1 1 auto; justify-content:center;
  }
  .btn-share-native{ background:#c1272d; }
  .btn-share-fb{ background:#1877f2; }
  .btn-share-zalo{ background:#0068ff; }
  .tt-share-link-row{ display:flex; gap:.4rem; }
  .tt-share-link-input{
    flex:1 1 auto; min-width:0; font-size:.85rem; padding:.6rem .7rem; border-radius:10px;
    border:2px solid #e8d9c3; background:#fff; color:#7a2e00;
  }
  .btn-copy{
    font-size:.9rem; font-weight:700; padding:.6rem .9rem; border-radius:10px;
    border:2px solid #c1272d; color:#c1272d; background:#fff; flex-shrink:0;
  }
  .btn-copy:active{ background:#fff3cd; }
  .tt-share-hint{ font-size:.9rem; color:#198754; font-weight:600; min-height:1.3em; }
</style>
</head>
<body>
  <div class="tt-wrap">
    <div class="tt-card">
      <img src="<?php echo base_url('assets/img/trung-thu-icon-removebg.png'); ?>" alt="Trung thu" class="tt-icon">
      <div class="tt-check"></div>
      <div class="tt-title">🎉 Đăng ký thành công!</div>
      <div class="tt-msg">
        Cảm ơn <strong><?php echo htmlspecialchars($parent_name); ?></strong> đã đăng ký nhận quà Trung Thu cho
        <strong><?php echo (int) $kid_count; ?></strong> bé tại Leno 🌕
      </div>
      <div class="tt-gift">🗓 Vui lòng đến nhận quà vào lúc:<br><?php echo $event_label; ?></div>
      <div class="tt-note">Hẹn gặp Ba/Mẹ và các bé trong Đêm hội Trăng Rằm nhé!</div>

      <div class="tt-share">
        <div class="tt-share-label">🔗 Chia sẻ niềm vui Trung Thu này:</div>
        <div class="tt-share-row">
          <button type="button" class="btn-share btn-share-native" id="nativeShareBtn"><i class="bi bi-share-fill"></i> Chia sẻ</button>
          <a class="btn-share btn-share-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($share_url); ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i> Facebook</a>
          <button type="button" class="btn-share btn-share-zalo" id="zaloShareBtn"><i class="bi bi-chat-dots-fill"></i> Zalo</button>
        </div>
        <div class="tt-share-hint" id="shareHint"></div>
      </div>
    </div>
  </div>

<script>
var shareUrl = <?php echo json_encode($share_url); ?>;

var nativeBtn = document.getElementById('nativeShareBtn');
if (navigator.share){
  nativeBtn.addEventListener('click', function(){
    navigator.share({ title: document.title, text: 'Tôi vừa đăng ký nhận quà Trung Thu cho bé tại Leno! 🌕', url: shareUrl }).catch(function(){});
  });
} else {
  nativeBtn.style.display = 'none';
}

function copyShareLink(onDone){
  if (navigator.clipboard && navigator.clipboard.writeText){
    navigator.clipboard.writeText(shareUrl).then(onDone).catch(function(){ fallbackCopy(); onDone(); });
  } else {
    fallbackCopy();
    onDone();
  }
}
function fallbackCopy(){
  var tmp = document.createElement('textarea');
  tmp.value = shareUrl;
  tmp.style.position = 'fixed';
  tmp.style.opacity = '0';
  document.body.appendChild(tmp);
  tmp.select();
  document.execCommand('copy');
  document.body.removeChild(tmp);
}

// Zalo chưa đăng ký App ID nên nút share widget của Zalo không dùng được — thay
// bằng sao chép link, dán trực tiếp vào Zalo vẫn tự hiện preview đẹp từ thẻ og:.
document.getElementById('zaloShareBtn').addEventListener('click', function(){
  copyShareLink(function(){
    document.getElementById('shareHint').textContent = '✅ Đã sao chép link! Mở Zalo và dán vào cuộc trò chuyện để chia sẻ.';
  });
});

var copyBtn = document.getElementById('copyLinkBtn');
if (copyBtn){
  copyBtn.addEventListener('click', function(){
    var btn = this;
    var input = document.getElementById('shareUrlInput');
    if (input){ input.select(); input.setSelectionRange(0, 99999); }
    copyShareLink(function(){
      var old = btn.textContent;
      btn.textContent = 'Đã sao chép!';
      setTimeout(function(){ btn.textContent = old; }, 1500);
    });
  });
}
</script>
</body>
</html>
