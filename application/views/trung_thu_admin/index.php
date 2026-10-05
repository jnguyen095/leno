<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">🌕 Đăng ký quà Trung Thu</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/trung-thu/admin/export'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-down"></i> Export Excel</a>
      <a href="<?php echo site_url('trung-thu'); ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right"></i> Xem form đăng ký</a>
    </div>
  </div>

  <?php if ( ! empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
      <span><i class="bi bi-clock-history"></i> Thời gian mở/đóng đăng ký</span>
      <span class="badge <?php echo $current_status['class']; ?>"><?php echo $current_status['label']; ?></span>
    </div>
    <div class="card-body">
      <div class="alert alert-light border py-2 small mb-3">
        Đang áp dụng: mở lúc <strong><?php echo $open_at_label; ?></strong> · đóng lúc <strong><?php echo $close_at_label; ?></strong>
      </div>
      <?php echo form_open(current_url()); ?>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Mở đăng ký lúc</label>
            <input type="datetime-local" name="trung_thu_open_at" id="openAtInput" class="form-control" value="<?php echo $trung_thu_open_at; ?>" onchange="updatePreview('openAtInput','openAtPreview')">
            <div class="form-text" id="openAtPreview"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Đóng đăng ký lúc</label>
            <input type="datetime-local" name="trung_thu_close_at" id="closeAtInput" class="form-control" value="<?php echo $trung_thu_close_at; ?>" onchange="updatePreview('closeAtInput','closeAtPreview')">
            <div class="form-text" id="closeAtPreview"></div>
          </div>
        </div>
        <div class="form-text">Để trống nghĩa là không giới hạn (mở sẵn / không tự đóng). Trước giờ mở, form sẽ báo "Chưa đến thời gian đăng ký"; sau giờ đóng, form sẽ báo "Chương trình đã kết thúc".</div>
        <button class="btn btn-brand mt-3">Lưu thời gian</button>
      <?php echo form_close(); ?>
    </div>
  </div>

  <script>
  function updatePreview(inputId, previewId){
    var val = document.getElementById(inputId).value;
    var preview = document.getElementById(previewId);
    if ( ! val){ preview.textContent = ''; return; }
    var parts = val.split('T');
    var d = parts[0].split('-');
    preview.innerHTML = '<strong>→ Ngày ' + d[2] + '/' + d[1] + '/' + d[0] + ' lúc ' + parts[1] + '</strong> — kiểm tra kỹ ngày/tháng trước khi lưu.';
  }
  updatePreview('openAtInput', 'openAtPreview');
  updatePreview('closeAtInput', 'closeAtPreview');
  </script>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-check text-brand fs-3"></i>
          <div class="fs-4 fw-bold mt-1"><?php echo $total_count; ?></div>
          <div class="text-muted small">Tổng lượt đăng ký</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <i class="bi bi-emoji-smile text-brand fs-3"></i>
          <div class="fs-4 fw-bold mt-1"><?php echo $total_kids; ?></div>
          <div class="text-muted small">Tổng số bé</div>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>Thời gian đăng ký</th><th>Tên Ba/Mẹ</th><th>Số điện thoại</th><th class="text-end">Số bé</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($registrations as $r): ?>
        <tr>
          <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($r['parent_name']); ?></td>
          <td><?php echo htmlspecialchars($r['phone']); ?></td>
          <td class="text-end"><?php echo (int) $r['kid_count']; ?></td>
          <td class="text-nowrap">
            <a href="<?php echo site_url('trung-thu/thank-you/'.$r['uuid']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Xem/chia sẻ trang cảm ơn"><i class="bi bi-link-45deg"></i></a>
            <a href="<?php echo site_url('me/trung-thu/admin/'.$r['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
            <?php echo form_open('me/trung-thu/admin/'.$r['id'].'/delete', array('class' => 'd-inline', 'onsubmit' => "return confirm('Xóa đăng ký này?');")); ?>
              <button class="btn btn-sm btn-outline-danger">Xóa</button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($registrations)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Chưa có đăng ký nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
