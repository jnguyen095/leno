<?php
  // Quản lý bàn: sắp xếp ngay trên danh sách — kéo thả (nắm ⋮⋮), gõ số vị trí hoặc nút lên / xuống, rồi "Lưu thứ tự"
  // (đánh số lại 1, 2, 3...; màn hình Bàn của POS web + ứng dụng xếp theo số này). Hành vi dùng chung: assets/js/sortable-list.js.
  // Bàn "Mang đi" không có ở đây (luôn đứng đầu màn hình Bàn).
?>
<div class="container py-3 py-md-4" style="max-width:960px">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1">Quản lý bàn</h4>
      <div class="text-muted small">Kéo <i class="bi bi-grip-vertical"></i> để đổi thứ tự, hoặc gõ số / bấm <i class="bi bi-arrow-up"></i><i class="bi bi-arrow-down"></i>, rồi bấm <b>Lưu thứ tự</b>. Màn hình Bàn (web và ứng dụng POS) xếp bàn theo thứ tự này.</div>
    </div>
    <a href="<?php echo site_url('me/tables/manage/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm bàn</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo $this->session->flashdata('error'); ?></div>
  <?php endif; ?>

  <?php if (empty($tables)): ?>
    <div class="orders-card text-center text-muted py-5">Chưa có bàn nào. Bấm <b>Thêm bàn</b> để tạo.</div>
  <?php else: ?>
  <?php echo form_open('me/tables/manage/reorder'); ?>
    <ul class="sort-list" data-sort-list data-bar="#tblSortBar" data-post-form="#tblPostForm">
      <?php foreach ($tables as $i => $t): $busy = $t['status'] !== 'AVAILABLE'; ?>
        <li class="sort-list-item" data-id="<?php echo $t['id']; ?>">
          <input type="hidden" name="ids[]" value="<?php echo $t['id']; ?>">
          <span class="sort-list-handle" data-bs-toggle="tooltip" title="Kéo để đổi thứ tự" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
          <input type="number" class="form-control form-control-sm sort-list-pos" min="1" max="<?php echo count($tables); ?>" value="<?php echo $i + 1; ?>"
                 aria-label="Vị trí của <?php echo htmlspecialchars($t['table_name']); ?>" inputmode="numeric">
          <span class="sort-list-name">
            <b><?php echo htmlspecialchars($t['table_name']); ?></b>
            <span class="badge bg-<?php echo table_status_badge($t['status']); ?> ms-1"><?php echo $t['status']; ?></span>
            <span class="d-block small text-muted">
              <code><?php echo htmlspecialchars($t['table_code']); ?></code> · <i class="bi bi-people"></i> <?php echo (int) $t['capacity']; ?> chỗ
              <?php if ( ! empty($t['note'])): ?> · <i class="bi bi-sticky"></i> <?php echo htmlspecialchars($t['note']); ?><?php endif; ?>
            </span>
          </span>
          <span class="sort-list-move">
            <button type="button" class="btn btn-sm btn-light js-up" data-bs-toggle="tooltip" title="Lên trên" aria-label="Lên trên"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm btn-light js-down" data-bs-toggle="tooltip" title="Xuống dưới" aria-label="Xuống dưới"><i class="bi bi-arrow-down"></i></button>
          </span>
          <span class="sort-list-actions">
            <a href="<?php echo site_url('me/tables/manage/'.$t['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa bàn"><i class="bi bi-pencil"></i></a>
            <?php if ($busy): ?>
              <button type="button" class="btn btn-sm btn-light text-warning-emphasis js-post" data-bs-toggle="tooltip" title="Đặt lại bàn về Trống"
                      data-action="<?php echo site_url('me/tables/manage/'.$t['id'].'/reset-status'); ?>"
                      data-confirm="<?php echo htmlspecialchars('Đặt lại "'.$t['table_name'].'" về Trống?'); ?>"
                      data-confirm-message="Đơn hàng đang mở của bàn (nếu có) sẽ bị huỷ." data-confirm-ok="Đặt lại"><i class="bi bi-arrow-counterclockwise"></i></button>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-light text-danger js-post" data-bs-toggle="tooltip" title="Xoá bàn"
                      data-action="<?php echo site_url('me/tables/manage/'.$t['id'].'/delete'); ?>"
                      data-confirm="<?php echo htmlspecialchars('Xoá "'.$t['table_name'].'"?'); ?>" data-confirm-ok="Xoá"><i class="bi bi-trash"></i></button>
            <?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="sort-list-bar" id="tblSortBar" hidden>
      <span class="small"><i class="bi bi-info-circle"></i> Thứ tự đã thay đổi — chưa lưu.</span>
      <span class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary js-sort-reset"><i class="bi bi-arrow-counterclockwise"></i> Hoàn tác</button>
        <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu thứ tự</button>
      </span>
    </div>
  <?php echo form_close(); ?>

  <?php // Form cho nút Xoá / Đặt lại trên từng dòng (form không lồng trong form thứ tự được). ?>
  <?php echo form_open('', array('id' => 'tblPostForm', 'class' => 'd-none', 'data-confirm' => '')); ?>
  <?php echo form_close(); ?>
  <?php endif; ?>
</div>

<?php if ( ! empty($tables)): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="<?php echo asset_url('assets/js/sortable-list.js'); ?>"></script>
<?php endif; ?>
