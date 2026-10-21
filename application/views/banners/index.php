<?php
  // Quản trị → Banner trang chủ: tải nhiều ảnh, sắp thứ tự, ẩn / hiện, mô tả + link; thời gian mỗi ảnh của băng chuyền.
  // Biến: $banners, $seconds, $shrink_kb, $max_files, $error, $success.
  $showing = count(array_filter($banners, function ($b) { return $b['status'] === 'ACTIVE'; }));
?>
<div class="container py-3 py-md-4" style="max-width:1000px;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-collection-play"></i> Banner trang chủ</h4>
      <div class="text-muted small">Ảnh lớn đầu trang chủ — nhiều ảnh thì tự chuyển lần lượt (khách cũng vuốt / bấm mũi tên được). Chưa có banner nào đang hiện thì trang chủ dùng ảnh mặc định.</div>
    </div>
    <a href="<?php echo base_url(); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i> Xem trang chủ</a>
  </div>

  <?php if ($error): ?><div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success py-2 small"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8 order-2 order-lg-1">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span>Danh sách banner</span>
          <span class="badge text-bg-light"><?php echo $showing; ?> đang hiện / <?php echo count($banners); ?> ảnh</span>
        </div>
        <div class="card-body">
          <?php if (empty($banners)): ?>
            <div class="text-muted text-center py-4">
              <i class="bi bi-images fs-1 d-block mb-2 opacity-50"></i>
              Chưa có banner nào. Chọn ảnh ở khung <b>Thêm banner</b> để bắt đầu.
            </div>
          <?php endif; ?>

          <?php foreach ($banners as $i => $b): $active = $b['status'] === 'ACTIVE'; ?>
            <div class="bn-item <?php echo $active ? '' : 'is-off'; ?>" id="banner-<?php echo $b['id']; ?>">
              <img src="<?php echo base_url('assets/'.$b['image']); ?>" alt="" class="bn-thumb" loading="lazy">
              <div class="bn-body">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge rounded-pill text-bg-secondary"><?php echo $i + 1; ?></span>
                  <?php echo $active ? '<span class="small text-success"><i class="bi bi-play-circle"></i> Đang hiện</span>' : '<span class="small text-muted"><i class="bi bi-eye-slash"></i> Đang ẩn</span>'; ?>
                </div>
                <?php // Mô tả + link: sửa ngay tại chỗ. ?>
                <?php echo form_open('me/banners/'.$b['id'].'/update', array('class' => 'bn-form')); ?>
                  <input type="text" name="title" maxlength="150" class="form-control form-control-sm" placeholder="Mô tả ảnh (không bắt buộc)"
                         value="<?php echo htmlspecialchars((string) $b['title']); ?>" aria-label="Mô tả ảnh">
                  <input type="text" name="link_url" maxlength="300" class="form-control form-control-sm" placeholder="Link khi bấm vào ảnh, vd /tin-tuc.html (không bắt buộc)"
                         value="<?php echo htmlspecialchars((string) $b['link_url']); ?>" aria-label="Link khi bấm vào ảnh">
                  <button class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-check2"></i> Lưu</button>
                <?php echo form_close(); ?>
              </div>
              <div class="bn-actions">
                <?php echo form_open('me/banners/'.$b['id'].'/move/up', array('class' => 'm-0')); ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Lên trước" <?php echo $i === 0 ? 'disabled' : ''; ?>><i class="bi bi-arrow-up"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/banners/'.$b['id'].'/move/down', array('class' => 'm-0')); ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Xuống sau" <?php echo $i === count($banners) - 1 ? 'disabled' : ''; ?>><i class="bi bi-arrow-down"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/banners/'.$b['id'].'/toggle', array('class' => 'm-0')); ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="<?php echo $active ? 'Ẩn khỏi trang chủ' : 'Hiện trên trang chủ'; ?>"><i class="bi <?php echo $active ? 'bi-eye-slash' : 'bi-eye'; ?>"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/banners/'.$b['id'].'/delete', array('class' => 'm-0', 'data-confirm' => html_escape('Xoá banner số '.($i + 1).'?'), 'data-confirm-message' => html_escape('Ảnh sẽ bị xoá khỏi máy chủ.'))); ?>
                  <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá banner"><i class="bi bi-trash"></i></button>
                <?php echo form_close(); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4 order-1 order-lg-2">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white fw-semibold">Thêm banner</div>
        <div class="card-body">
          <?php echo form_open_multipart('me/banners/upload', array('id' => 'bnUploadForm')); ?>
            <label class="bn-drop" for="bnFiles">
              <i class="bi bi-cloud-arrow-up"></i>
              <span id="bnDropText">Bấm để chọn ảnh<br><small>Chọn được nhiều ảnh cùng lúc (tối đa <?php echo $max_files; ?>)</small></span>
              <input type="file" name="images[]" id="bnFiles" accept="image/jpeg,image/png,image/webp" multiple hidden>
            </label>
            <div class="form-text">Nên dùng ảnh ngang tỉ lệ <b>8:3</b> (vd 2048×768). Ảnh lớn hơn <?php echo round($shrink_kb / 1024); ?> MB được tự thu nhỏ. Phần chữ quan trọng nên để giữa ảnh — mép ảnh có thể bị cắt trên điện thoại.</div>
            <button class="btn btn-brand w-100 mt-2" id="bnUploadBtn" disabled><i class="bi bi-upload"></i> Tải lên</button>
          <?php echo form_close(); ?>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold">Tự chuyển ảnh</div>
        <div class="card-body">
          <?php echo form_open('me/banners/settings'); ?>
            <label class="form-label" for="bnSeconds">Mỗi ảnh hiện trong</label>
            <div class="input-group">
              <input type="number" name="seconds" id="bnSeconds" class="form-control" min="3" max="20" value="<?php echo (int) $seconds; ?>">
              <span class="input-group-text">giây</span>
              <button class="btn btn-outline-primary">Lưu</button>
            </div>
            <div class="form-text">Từ 3 đến 20 giây. Tạm dừng khi khách rê chuột vào ảnh.</div>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo asset_url('assets/js/image-compress.js'); ?>"></script>
<script>
(function(){
  // Chọn ảnh → thu nhỏ trên trình duyệt ảnh nào lớn hơn 2MB → bật nút Tải lên.
  var input = document.getElementById('bnFiles'), btn = document.getElementById('bnUploadBtn'), text = document.getElementById('bnDropText');
  var LIMIT = <?php echo (int) $shrink_kb * 1024; ?>, MAX = <?php echo (int) $max_files; ?>;
  input.addEventListener('change', async function(){
    var files = Array.from(input.files).slice(0, MAX);
    if ( ! files.length) { btn.disabled = true; return; }
    btn.disabled = true;
    text.innerHTML = 'Đang chuẩn bị ' + files.length + ' ảnh...';
    var dt = new DataTransfer();
    for (var i = 0; i < files.length; i++) {
      var f = files[i];
      dt.items.add(f.size > LIMIT && window.papCompressImage ? await window.papCompressImage(f, LIMIT, 2400) : f);
    }
    input.files = dt.files;
    text.innerHTML = '<b>' + files.length + ' ảnh</b> đã chọn<br><small>Bấm "Tải lên" để thêm, hoặc bấm vào đây để chọn lại</small>';
    btn.disabled = false;
  });
  document.getElementById('bnUploadForm').addEventListener('submit', function(){
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tải lên...';
  });
})();
</script>
