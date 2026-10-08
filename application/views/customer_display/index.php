<div class="container py-3 py-md-4" style="max-width:960px;">
  <h4 class="fw-bold mb-1"><i class="bi bi-display"></i> Màn hình khách</h4>
  <p class="text-muted small mb-3">
    Màn hình phụ của máy POS (quay về phía khách): lúc rảnh trình chiếu ảnh bên dưới; khi đang gọi món hiện danh sách món
    và tổng tiền; khi thanh toán chuyển khoản hiện mã VietQR (lấy theo Cài đặt → Chuyển khoản).
  </p>

  <?php if ($error): ?><div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success py-2 small"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

  <div class="row g-3">
    <!-- Ảnh trình chiếu -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span>Ảnh trình chiếu lúc rảnh</span>
          <span class="badge text-bg-light"><?php echo count($slides); ?> ảnh</span>
        </div>
        <div class="card-body">
          <?php echo form_open_multipart('me/customer-display/upload', array('class' => 'border rounded-3 p-3 mb-3 bg-light')); ?>
            <div class="row g-2 align-items-end">
              <div class="col-sm-7">
                <label class="form-label small mb-1">Chọn ảnh (JPG, PNG, WebP — tối đa <?php echo round($max_kb / 1024); ?> MB)</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control" required>
              </div>
              <div class="col-sm-5">
                <label class="form-label small mb-1">Tiêu đề (không bắt buộc)</label>
                <input type="text" name="title" maxlength="150" class="form-control" placeholder="VD: Khuyến mãi tháng 10">
              </div>
            </div>
            <div class="form-text">
              Nên dùng ảnh ngang 1920×1080 (16:9). Ảnh được phóng cho đầy màn hình, phần thừa ở mép sẽ bị cắt.
              <?php if ( ! $can_resize): ?>Máy chủ chưa bật thư viện ảnh GD nên ảnh được giữ nguyên kích thước — hãy tải ảnh vừa đủ lớn.<?php endif; ?>
            </div>
            <button class="btn btn-brand mt-2"><i class="bi bi-upload"></i> Thêm ảnh</button>
          <?php echo form_close(); ?>

          <?php if (empty($slides)): ?>
            <div class="text-muted text-center py-4">Chưa có ảnh nào. Lúc rảnh màn hình khách sẽ hiện logo, tên quán và lời chào.</div>
          <?php endif; ?>

          <?php foreach ($slides as $i => $s):
            $active = $s['status'] === 'ACTIVE';
            $expired = $s['end_date'] && $s['end_date'] < $today;
            $upcoming = $s['start_date'] && $s['start_date'] > $today;
          ?>
          <div class="border rounded-3 p-2 mb-2 <?php echo $active ? '' : 'opacity-50'; ?>">
            <div class="d-flex gap-2 align-items-start">
              <img src="<?php echo base_url('assets/'.$s['image']); ?>" alt="" class="rounded-2 flex-shrink-0"
                   style="width:160px;height:90px;object-fit:cover;background:#eee;">
              <div class="flex-grow-1 min-w-0">
                <?php echo form_open('me/customer-display/'.$s['id'].'/update'); ?>
                  <div class="d-flex gap-2 mb-1">
                    <span class="badge rounded-pill text-bg-secondary align-self-center"><?php echo $i + 1; ?></span>
                    <input type="text" name="title" maxlength="150" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars((string) $s['title']); ?>" placeholder="Tiêu đề">
                    <div class="input-group input-group-sm flex-shrink-0" style="width:110px;" title="Thời gian hiện ảnh này (để trống = mặc định)">
                      <input type="number" name="duration_seconds" min="3" max="120" class="form-control"
                             value="<?php echo $s['duration_seconds']; ?>" placeholder="<?php echo $config['slide_seconds']; ?>">
                      <span class="input-group-text">giây</span>
                    </div>
                  </div>
                  <div class="row g-1 small">
                    <div class="col-6">
                      <label class="text-muted mb-0">Hiện từ ngày</label>
                      <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo $s['start_date']; ?>">
                    </div>
                    <div class="col-6">
                      <label class="text-muted mb-0">Đến ngày</label>
                      <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo $s['end_date']; ?>">
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2 mt-1 small">
                    <button class="btn btn-sm btn-outline-brand">Lưu</button>
                    <?php if ( ! $active): ?><span class="text-muted">Đang ẩn</span>
                    <?php elseif ($expired): ?><span class="text-danger">Đã hết hạn</span>
                    <?php elseif ($upcoming): ?><span class="text-warning">Chưa tới ngày</span>
                    <?php else: ?><span class="text-success">Đang trình chiếu</span><?php endif; ?>
                  </div>
                <?php echo form_close(); ?>
              </div>
              <div class="d-flex flex-column gap-1">
                <?php echo form_open('me/customer-display/'.$s['id'].'/move/up', array('class' => 'm-0')); ?>
                  <button class="btn btn-sm btn-light" title="Lên trước" <?php echo $i === 0 ? 'disabled' : ''; ?>><i class="bi bi-arrow-up"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/customer-display/'.$s['id'].'/move/down', array('class' => 'm-0')); ?>
                  <button class="btn btn-sm btn-light" title="Xuống sau" <?php echo $i === count($slides) - 1 ? 'disabled' : ''; ?>><i class="bi bi-arrow-down"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/customer-display/'.$s['id'].'/toggle', array('class' => 'm-0')); ?>
                  <button class="btn btn-sm btn-light" title="<?php echo $active ? 'Ẩn' : 'Hiện'; ?>"><i class="bi <?php echo $active ? 'bi-eye-slash' : 'bi-eye'; ?>"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/customer-display/'.$s['id'].'/delete', array('class' => 'm-0', 'onsubmit' => "return confirm('Xoá ảnh này?');")); ?>
                  <button class="btn btn-sm btn-light text-danger" title="Xoá"><i class="bi bi-trash"></i></button>
                <?php echo form_close(); ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Tuỳ chọn hiển thị -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold">Tuỳ chọn hiển thị</div>
        <div class="card-body">
          <?php echo form_open('me/customer-display'); ?>
            <div class="mb-3">
              <label class="form-label">Thời gian mỗi ảnh</label>
              <div class="input-group">
                <input type="number" name="slide_seconds" min="3" max="120" class="form-control" value="<?php echo $config['slide_seconds']; ?>" required>
                <span class="input-group-text">giây</span>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Lời chào (lúc rảnh)</label>
              <input type="text" name="welcome_text" maxlength="150" class="form-control" value="<?php echo htmlspecialchars($config['welcome_text']); ?>">
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" role="switch" name="show_logo" value="1" id="showLogo" <?php echo $config['show_logo'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="showLogo">Hiện logo, tên quán và lời chào trên ảnh trình chiếu</label>
            </div>
            <div class="mb-3">
              <label class="form-label">Lời cảm ơn sau khi thanh toán</label>
              <input type="text" name="thanks_text" maxlength="150" class="form-control" value="<?php echo htmlspecialchars($config['thanks_text']); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Hiện lời cảm ơn trong</label>
              <div class="input-group">
                <input type="number" name="thanks_seconds" min="2" max="60" class="form-control" value="<?php echo $config['thanks_seconds']; ?>" required>
                <span class="input-group-text">giây</span>
              </div>
            </div>
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" role="switch" name="show_qr" value="1" id="showQr" <?php echo $config['show_qr'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="showQr">Hiện mã VietQR khi khách chọn chuyển khoản</label>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" role="switch" name="show_item_notes" value="1" id="showNotes" <?php echo $config['show_item_notes'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="showNotes">Hiện ghi chú món (vd "Ít đá") cho khách xem</label>
            </div>
            <div class="mb-3">
              <label class="form-label">Cỡ chữ</label>
              <select name="text_scale" class="form-select">
                <?php foreach (array('0.8' => 'Nhỏ', '1' => 'Vừa', '1.2' => 'Lớn', '1.4' => 'Rất lớn', '1.6' => 'Cực lớn') as $val => $label): ?>
                  <option value="<?php echo $val; ?>" <?php echo abs($config['text_scale'] - (float) $val) < 0.01 ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Màn hình nhỏ (7–8") nên chọn Vừa; màn hình lớn hoặc khách đứng xa chọn Lớn.</div>
            </div>
            <button class="btn btn-brand w-100">Lưu tuỳ chọn</button>
          <?php echo form_close(); ?>
        </div>
      </div>
      <p class="text-muted small mt-2">Ứng dụng POS tự tải lại ảnh và tuỳ chọn mỗi 5 phút hoặc khi mở lại ứng dụng.</p>
    </div>
  </div>
</div>
