<?php
  // Danh mục sản phẩm: sắp xếp ngay trên danh sách — kéo thả (chuột / ngón tay, nắm biểu tượng ⋮⋮), gõ số thứ tự,
  // hoặc nút lên / xuống — rồi bấm "Lưu thứ tự" (đánh số lại 1, 2, 3...). Thứ tự này dùng cho POS, thực đơn website, báo cáo.
  // Hành vi sắp xếp dùng chung: assets/js/sortable-list.js.
?>
<div class="container py-3 py-md-4" style="max-width:860px">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1">Danh mục sản phẩm</h4>
      <div class="text-muted small">Kéo <i class="bi bi-grip-vertical"></i> để đổi thứ tự, hoặc gõ số / bấm <i class="bi bi-arrow-up"></i><i class="bi bi-arrow-down"></i>, rồi bấm <b>Lưu thứ tự</b>. Thứ tự này dùng cho màn hình gọi món, thực đơn website và báo cáo.</div>
    </div>
    <a href="<?php echo site_url('me/categories/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (empty($categories)): ?>
    <div class="orders-card text-center text-muted py-5">Chưa có danh mục nào. Bấm <b>Thêm</b> để tạo.</div>
  <?php else: ?>
  <?php echo form_open('me/categories/reorder'); ?>
    <ul class="sort-list" data-sort-list data-bar="#catSortBar" data-post-form="#catPostForm">
      <?php foreach ($categories as $i => $c): $off = $c['status'] !== 'ACTIVE'; ?>
        <li class="sort-list-item <?php echo $off ? 'is-off' : ''; ?>" data-id="<?php echo $c['id']; ?>">
          <input type="hidden" name="ids[]" value="<?php echo $c['id']; ?>">
          <span class="sort-list-handle" data-bs-toggle="tooltip" title="Kéo để đổi thứ tự" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
          <input type="number" class="form-control form-control-sm sort-list-pos" min="1" max="<?php echo count($categories); ?>" value="<?php echo $i + 1; ?>"
                 aria-label="Vị trí của <?php echo htmlspecialchars($c['name']); ?>" inputmode="numeric">
          <span class="sort-list-name">
            <b><?php echo htmlspecialchars($c['name']); ?></b>
            <?php if ($off): ?><span class="badge bg-secondary ms-1">Đã ẩn</span><?php endif; ?>
            <?php $cnt = isset($counts[$c['id']]) ? $counts[$c['id']] : array('total' => 0, 'active' => 0); ?>
            <span class="d-block small text-muted"><?php echo $cnt['active']; ?> món đang bán<?php echo $cnt['total'] > $cnt['active'] ? ' · '.($cnt['total'] - $cnt['active']).' đã ẩn' : ''; ?></span>
          </span>
          <span class="sort-list-move">
            <button type="button" class="btn btn-sm btn-light js-up" data-bs-toggle="tooltip" title="Lên trên" aria-label="Lên trên"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm btn-light js-down" data-bs-toggle="tooltip" title="Xuống dưới" aria-label="Xuống dưới"><i class="bi bi-arrow-down"></i></button>
          </span>
          <span class="sort-list-actions">
            <a href="<?php echo site_url('me/categories/'.$c['id'].'/products'); ?>" class="btn btn-sm btn-light text-primary" data-bs-toggle="tooltip" title="Sắp xếp món trong danh mục"><i class="bi bi-sort-down"></i></a>
            <a href="<?php echo site_url('me/categories/'.$c['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa danh mục"><i class="bi bi-pencil"></i></a>
            <?php if ( ! $off): ?>
              <button type="button" class="btn btn-sm btn-light text-danger js-post" data-bs-toggle="tooltip" title="Ẩn danh mục"
                      data-action="<?php echo site_url('me/categories/'.$c['id'].'/delete'); ?>"
                      data-confirm="<?php echo htmlspecialchars('Ẩn danh mục "'.$c['name'].'"?'); ?>"
                      data-confirm-message="Danh mục bị ẩn sẽ không hiện trên thực đơn." data-confirm-ok="Ẩn"><i class="bi bi-eye-slash"></i></button>
            <?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="sort-list-bar" id="catSortBar" hidden>
      <span class="small"><i class="bi bi-info-circle"></i> Thứ tự đã thay đổi — chưa lưu.</span>
      <span class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary js-sort-reset"><i class="bi bi-arrow-counterclockwise"></i> Hoàn tác</button>
        <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu thứ tự</button>
      </span>
    </div>
  <?php echo form_close(); ?>

  <?php // Form cho nút thao tác trên từng dòng (form không lồng trong form thứ tự được). ?>
  <?php echo form_open('', array('id' => 'catPostForm', 'class' => 'd-none', 'data-confirm' => '')); ?>
  <?php echo form_close(); ?>
  <?php endif; ?>
</div>

<?php if ( ! empty($categories)): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="<?php echo asset_url('assets/js/sortable-list.js'); ?>"></script>
<?php endif; ?>
