<?php
  // Sắp xếp món trong một danh mục: kéo thả (nắm ⋮⋮), gõ số vị trí hoặc nút lên / xuống, rồi "Lưu thứ tự".
  // POS web, ứng dụng POS và thực đơn website xếp món theo thứ tự này. Hành vi dùng chung: assets/js/sortable-list.js.
  // Biến: $category, $categories (đổi nhanh danh mục), $counts, $products.
  $price = function ($v) { return number_format((float) $v, 0, ',', '.').'đ'; };
?>
<div class="container py-3 py-md-4" style="max-width:860px">
  <a href="<?php echo site_url('me/categories'); ?>" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Danh mục</a>
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-sort-down"></i> Sắp xếp món</h4>
      <div class="text-muted small">Kéo <i class="bi bi-grip-vertical"></i> để đổi thứ tự, hoặc gõ số / bấm <i class="bi bi-arrow-up"></i><i class="bi bi-arrow-down"></i>, rồi bấm <b>Lưu thứ tự</b>. Màn hình gọi món (web, ứng dụng POS) và thực đơn website xếp món theo thứ tự này.</div>
    </div>
    <?php // Đổi nhanh sang danh mục khác (không phải quay lại trang Danh mục). ?>
    <div style="min-width:220px">
      <label class="form-label small text-muted mb-1" for="spCategory">Danh mục</label>
      <select id="spCategory" class="form-select" onchange="location.href=this.value">
        <?php foreach ($categories as $c): $n = isset($counts[$c['id']]) ? $counts[$c['id']]['total'] : 0; ?>
          <option value="<?php echo site_url('me/categories/'.$c['id'].'/products'); ?>" <?php echo (int) $c['id'] === (int) $category['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($c['name']).' ('.$n.' món)'.($c['status'] !== 'ACTIVE' ? ' — đã ẩn' : ''); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (empty($products)): ?>
    <div class="orders-card text-center text-muted py-5">
      Danh mục "<?php echo htmlspecialchars($category['name']); ?>" chưa có món nào.
      <div class="mt-2"><a href="<?php echo site_url('me/products/create'); ?>" class="btn btn-brand btn-sm"><i class="bi bi-plus-lg"></i> Thêm món</a></div>
    </div>
  <?php elseif (count($products) === 1): ?>
    <div class="orders-card text-center text-muted py-4">Danh mục chỉ có 1 món — không cần sắp xếp.</div>
  <?php else: ?>
  <?php echo form_open('me/categories/'.$category['id'].'/products/reorder'); ?>
    <ul class="sort-list" data-sort-list data-bar="#spSortBar">
      <?php foreach ($products as $i => $p): $off = $p['status'] !== 'ACTIVE'; ?>
        <li class="sort-list-item <?php echo $off ? 'is-off' : ''; ?>" data-id="<?php echo $p['id']; ?>">
          <input type="hidden" name="ids[]" value="<?php echo $p['id']; ?>">
          <span class="sort-list-handle" data-bs-toggle="tooltip" title="Kéo để đổi thứ tự" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
          <input type="number" class="form-control form-control-sm sort-list-pos" min="1" max="<?php echo count($products); ?>" value="<?php echo $i + 1; ?>"
                 aria-label="Vị trí của <?php echo htmlspecialchars($p['product_name']); ?>" inputmode="numeric">
          <span class="sort-list-name sort-list-with-thumb">
            <span class="sort-list-thumb">
              <?php if ($p['image']): ?><img src="<?php echo base_url('assets/'.$p['image']); ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-cup-hot"></i><?php endif; ?>
            </span>
            <span class="min-w-0">
              <b><?php echo htmlspecialchars($p['product_name']); ?></b>
              <?php if ($off): ?><span class="badge bg-secondary ms-1">Đã ẩn</span><?php endif; ?>
              <span class="d-block small text-muted"><code><?php echo htmlspecialchars($p['sku']); ?></code> · <?php echo $price($p['price']); ?></span>
            </span>
          </span>
          <span class="sort-list-move">
            <button type="button" class="btn btn-sm btn-light js-up" data-bs-toggle="tooltip" title="Lên trên" aria-label="Lên trên"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm btn-light js-down" data-bs-toggle="tooltip" title="Xuống dưới" aria-label="Xuống dưới"><i class="bi bi-arrow-down"></i></button>
          </span>
          <span class="sort-list-actions">
            <a href="<?php echo site_url('me/products/'.$p['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa món"><i class="bi bi-pencil"></i></a>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="sort-list-bar" id="spSortBar" hidden>
      <span class="small"><i class="bi bi-info-circle"></i> Thứ tự đã thay đổi — chưa lưu.</span>
      <span class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary js-sort-reset"><i class="bi bi-arrow-counterclockwise"></i> Hoàn tác</button>
        <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu thứ tự</button>
      </span>
    </div>
  <?php echo form_close(); ?>
  <?php endif; ?>
</div>

<?php if (count($products) > 1): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="<?php echo asset_url('assets/js/sortable-list.js'); ?>"></script>
<?php endif; ?>
