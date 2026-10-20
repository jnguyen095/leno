<?php
  // Quản trị → Thư viện ảnh của Trang nội dung: ảnh trong assets/uploads/pages/ — xem, tải lên, chép link, xoá.
  $kb = function ($b) { return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : number_format(ceil($b / 1024), 0, ',', '.').' KB'; };
  $total = array_sum(array_column($images, 'size'));
?>
<div class="container-xl py-3 py-md-4">
  <a href="<?php echo site_url('me/pages'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Trang nội dung</a>
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-images"></i> Thư viện ảnh</h4>
      <div class="text-muted small">Ảnh dùng trong nội dung các trang website · <?php echo count($images); ?> ảnh · <?php echo $kb($total); ?></div>
    </div>
    <?php echo form_open_multipart('me/pages/images/upload', array('id' => 'imgUploadForm')); ?>
      <label class="btn btn-brand mb-0" id="imgUploadBtn"><i class="bi bi-upload"></i> <span>Tải ảnh lên</span>
        <input type="file" name="image" id="imgUploadInput" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
      </label>
    <?php echo form_close(); ?>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <?php if (empty($images)): ?>
    <div class="orders-card text-center text-muted py-5">
      <i class="bi bi-images fs-1 d-block mb-2 opacity-50"></i>
      Chưa có ảnh nào. Bấm <b>Tải ảnh lên</b> (JPG, PNG, WEBP, GIF — ảnh lớn hơn <?php echo round($shrink_kb / 1024); ?> MB được tự thu nhỏ) hoặc kéo thả ảnh khi soạn trang.
    </div>
  <?php else: ?>
  <div class="pg-lib">
    <?php foreach ($images as $img): $used = $img['used_in']; ?>
      <div class="pg-lib-item">
        <a href="<?php echo htmlspecialchars($img['url']); ?>" target="_blank" rel="noopener" class="pg-lib-thumb">
          <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="" loading="lazy">
        </a>
        <div class="pg-lib-body">
          <div class="small text-truncate fw-semibold" title="<?php echo htmlspecialchars($img['name']); ?>"><?php echo htmlspecialchars($img['name']); ?></div>
          <div class="small text-muted">
            <?php echo $img['width'] ? $img['width'].'×'.$img['height'].' · ' : ''; ?><?php echo $kb($img['size']); ?> · <?php echo date('d/m/Y', $img['time']); ?>
          </div>
          <div class="small mt-1">
            <?php if ($used): ?>
              <span class="text-success"><i class="bi bi-link-45deg"></i> Dùng ở:</span>
              <?php $links = array(); foreach ($used as $u) $links[] = '<a href="'.site_url(Page_model::public_path($u)).'" target="_blank" rel="noopener">'.htmlspecialchars($u['title']).'</a>'; echo implode(', ', $links); ?>
            <?php else: ?>
              <span class="text-muted">Chưa dùng ở trang nào</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="pg-lib-actions">
          <button type="button" class="btn btn-sm btn-light js-copy" data-url="<?php echo htmlspecialchars($img['url']); ?>" data-bs-toggle="tooltip" title="Chép link ảnh"><i class="bi bi-clipboard"></i></button>
          <?php echo form_open('me/pages/images/delete', array('class' => 'd-inline',
                  'data-confirm' => html_escape('Xoá ảnh '.$img['name'].'?'),
                  'data-confirm-message' => html_escape($used ? 'Ảnh đang dùng ở '.count($used).' trang — trang đó sẽ mất ảnh này.' : 'Ảnh chưa dùng ở trang nào.'))); ?>
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($img['name']); ?>">
            <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá ảnh"><i class="bi bi-trash"></i></button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<script src="<?php echo asset_url('assets/js/image-compress.js'); ?>"></script>
<script>
// Chọn ảnh → thu nhỏ trên trình duyệt nếu lớn hơn 2MB → gửi form.
document.getElementById('imgUploadInput').addEventListener('change', function(){
  var input = this, file = input.files && input.files[0], limit = <?php echo (int) $shrink_kb * 1024; ?>;
  if ( ! file) return;
  var label = document.querySelector('#imgUploadBtn span');
  if (file.size <= limit || ! window.papCompressImage) { input.form.submit(); return; }
  label.textContent = 'Đang thu nhỏ ảnh...';
  window.papCompressImage(file, limit, 1920).then(function(small){
    if (small !== file) { var dt = new DataTransfer(); dt.items.add(small); input.files = dt.files; }
    label.textContent = 'Đang tải lên...';
    input.form.submit();
  });
});
document.querySelectorAll('.js-copy').forEach(function(btn){
  btn.addEventListener('click', function(){
    var url = btn.dataset.url;
    (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).catch(function(){ window.prompt('Chép link ảnh:', url); });
    btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
    setTimeout(function(){ btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
  });
});
</script>
