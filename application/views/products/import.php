<div class="container py-3 py-md-4" style="max-width:900px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Import sản phẩm</h4>
    <a href="<?php echo site_url('me/products'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Danh sách sản phẩm</a>
  </div>

  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <?php if ($preview === NULL): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-body">
        <p class="text-muted small mb-3">
          File cần có các cột theo đúng thứ tự: <strong>SKU, Tên sản phẩm, Danh mục, Giá, Mô tả, Danh mục kho (tuỳ chọn), Quản lý kho (YES/NO), Trạng thái</strong>.
          "Danh mục" là danh mục thực đơn bán hàng; "Danh mục kho" (nếu điền) phải khớp với 1 <a href="<?php echo site_url('me/inventory/categories'); ?>" target="_blank">danh mục kho</a> đã có.
          Nếu SKU đã tồn tại, sản phẩm sẽ được cập nhật thông tin (ảnh hiện có giữ nguyên, Excel không mang được ảnh).
        </p>
        <a href="<?php echo site_url('me/products/import-template'); ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download"></i> Tải file mẫu (CSV)</a>
        <?php echo form_open_multipart(current_url()); ?>
          <div class="mb-3">
            <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
          </div>
          <button class="btn btn-brand">Tải lên &amp; Xem trước</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info py-2 small">
      <?php echo count($preview['valid']); ?> dòng hợp lệ sẽ được nhập/cập nhật.
      <?php if (count($preview['invalid']) > 0): ?> <?php echo count($preview['invalid']); ?> dòng bị lỗi sẽ <strong>bị bỏ qua</strong>.<?php endif; ?>
    </div>

    <?php if (count($preview['invalid']) > 0): ?>
      <h6 class="fw-bold text-danger">Dòng lỗi (sẽ không import)</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-danger bg-white">
          <thead><tr><th>Dòng</th><th>SKU</th><th>Tên</th><th>Lỗi</th></tr></thead>
          <tbody>
          <?php foreach ($preview['invalid'] as $r): ?>
            <tr>
              <td><?php echo $r['line']; ?></td>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo htmlspecialchars(implode('; ', $r['errors'])); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if (count($preview['valid']) > 0): ?>
      <h6 class="fw-bold text-success">Dòng hợp lệ</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm bg-white shadow-sm rounded">
          <thead class="table-light"><tr><th>Dòng</th><th>SKU</th><th>Tên</th><th>Danh mục</th><th class="text-end">Giá</th><th>Danh mục kho</th><th>Quản lý kho</th><th>Trạng thái</th></tr></thead>
          <tbody>
          <?php foreach ($preview['valid'] as $r): ?>
            <tr>
              <td><?php echo $r['line']; ?></td>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['product_name']); ?></td>
              <td><?php echo htmlspecialchars($r['category_name']); ?></td>
              <td class="text-end"><?php echo number_format($r['price']); ?></td>
              <td><?php echo $r['inventory_category_name'] ? htmlspecialchars($r['inventory_category_name']) : '—'; ?></td>
              <td><?php echo $r['track_inventory'] ? '<span class="badge bg-info text-dark">Có</span>' : '<span class="text-muted">Không</span>'; ?></td>
              <td><?php echo htmlspecialchars($r['status']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php echo form_open(site_url('me/products/import')); ?>
        <input type="hidden" name="rows_json" value='<?php echo htmlspecialchars(json_encode($preview['valid']), ENT_QUOTES); ?>'>
        <div class="d-flex gap-2">
          <button class="btn btn-brand"><i class="bi bi-check-lg"></i> Xác nhận nhập <?php echo count($preview['valid']); ?> dòng</button>
          <a href="<?php echo site_url('me/products/import'); ?>" class="btn btn-outline-secondary">Chọn file khác</a>
        </div>
      <?php echo form_close(); ?>
    <?php else: ?>
      <a href="<?php echo site_url('me/products/import'); ?>" class="btn btn-outline-secondary">Chọn file khác</a>
    <?php endif; ?>
  <?php endif; ?>
</div>
