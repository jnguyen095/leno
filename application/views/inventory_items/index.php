<div class="container-fluid py-3 py-md-4">
  <?php
    $base_qs = array();
    if ($category_id) $base_qs['category_id'] = $category_id;
    if ($keyword) $base_qs['q'] = $keyword;

    $export_qs = $base_qs;
    if ($stock_status) $export_qs['stock_status'] = $stock_status;
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Hàng Trong Kho</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/inventory/items/print').($export_qs ? '?'.http_build_query($export_qs) : ''); ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> In danh sách</a>
      <a href="<?php echo site_url('me/inventory/items/export').($export_qs ? '?'.http_build_query($export_qs) : ''); ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-down"></i> Export Excel</a>
      <?php if ($current_user['role'] === 'ADMIN'): ?>
      <a href="<?php echo site_url('me/inventory/items/import'); ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-up"></i> Import Excel</a>
      <a href="<?php echo site_url('me/inventory/items/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm sản phẩm</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <?php echo form_open('me/inventory/items', array('method' => 'get', 'class' => 'row g-2 mb-3')); ?>
    <?php if ($stock_status): ?><input type="hidden" name="stock_status" value="<?php echo htmlspecialchars($stock_status); ?>"><?php endif; ?>
    <div class="col-auto">
      <input type="text" name="q" class="form-control" placeholder="Tìm theo tên hoặc SKU..." value="<?php echo htmlspecialchars($keyword); ?>" style="min-width:220px;">
    </div>
    <div class="col-auto">
      <select name="category_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tất cả danh mục</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?php echo $c['id']; ?>" <?php echo ((string) $category_id === (string) $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($current_user['role'] === 'ADMIN'): ?>
    <div class="col-auto">
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="ACTIVE" <?php echo $status === 'ACTIVE' ? 'selected' : ''; ?>>Đang hoạt động</option>
        <option value="INACTIVE" <?php echo $status === 'INACTIVE' ? 'selected' : ''; ?>>Đã xoá</option>
        <option value="ALL" <?php echo $status === 'ALL' ? 'selected' : ''; ?>>Tất cả</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
      <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Tìm</button>
    </div>
    <div class="col-auto">
      <a href="<?php echo site_url('me/inventory/items').'?'.http_build_query(array_merge($base_qs, $stock_status === 'OK' ? array() : array('stock_status' => 'OK'))); ?>"
         class="btn <?php echo $stock_status === 'OK' ? 'btn-success' : 'btn-outline-success'; ?>">
        <i class="bi bi-check-circle"></i> Đủ hàng
      </a>
      <a href="<?php echo site_url('me/inventory/items').'?'.http_build_query(array_merge($base_qs, $stock_status === 'LOW' ? array() : array('stock_status' => 'LOW'))); ?>"
         class="btn <?php echo $stock_status === 'LOW' ? 'btn-danger' : 'btn-outline-danger'; ?>">
        <i class="bi bi-exclamation-triangle"></i> Sắp hết hàng
      </a>
    </div>
  <?php echo form_close(); ?>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light">
        <tr><th>STT</th><th>Ảnh</th><th>Tên</th><th>Danh mục</th><th>ĐVT</th><th>Giá/ĐV cơ sở</th><th>Bảo quản</th><th class="text-end">Tồn kho</th><th class="text-end">Ngưỡng</th><th>Trạng thái</th><?php if ($current_user['role'] === 'ADMIN'): ?><th></th><?php endif; ?></tr>
      </thead>
      <tbody>
      <?php $stt = 1; foreach ($items as $it): $low = $it['qty_on_hand'] < $it['low_stock_threshold']; ?>
        <tr class="<?php echo $low ? 'table-danger' : ''; ?>">
          <td><?php echo $stt++; ?></td>
          <td>
            <?php if ($it['image']): ?>
              <img src="<?php echo base_url('assets/'.$it['image']); ?>" style="width:48px;height:48px;object-fit:cover;cursor:zoom-in;" class="rounded border" onclick="papOpenImageLightbox(this);">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted" style="width:48px;height:48px;"><i class="bi bi-box-seam"></i></div>
            <?php endif; ?>
          </td>
          <td>
            <?php echo htmlspecialchars($it['name']); ?>
            <?php if ($it['status'] === 'INACTIVE'): ?><span class="badge bg-secondary">Đã xoá</span><?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($it['category_name']); ?></td>
          <td><?php echo htmlspecialchars($it['unit_name']); ?></td>
          <td class="small text-muted">
            <?php if ($it['base_unit'] && $it['base_unit_cost'] !== NULL): ?>
              <?php echo money_format_vnd($it['base_unit_cost']); ?>/<?php echo htmlspecialchars($it['base_unit']); ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?php echo storage_type_label($it['storage_type']); ?></td>
          <td class="text-end fw-semibold"><?php echo rtrim(rtrim(number_format($it['qty_on_hand'], 2, '.', ''), '0'), '.'); ?></td>
          <td class="text-end text-muted"><?php echo rtrim(rtrim(number_format($it['low_stock_threshold'], 2, '.', ''), '0'), '.'); ?></td>
          <td>
            <?php if ($low): ?><span class="badge bg-danger">Sắp hết</span><?php else: ?><span class="badge bg-success">Đủ hàng</span><?php endif; ?>
          </td>
          <?php if ($current_user['role'] === 'ADMIN'): ?>
          <td class="text-nowrap">
            <a href="<?php echo site_url('me/inventory/items/'.$it['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
            <?php if ($it['status'] === 'ACTIVE'): ?>
            <?php echo form_open('me/inventory/items/'.$it['id'].'/delete', array('class' => 'd-inline', 'onsubmit' => "return confirm('Xóa sản phẩm kho này? Sản phẩm sẽ chuyển sang trạng thái Đã xoá, có thể khôi phục lại qua Sửa.');")); ?>
              <button class="btn btn-sm btn-outline-danger">Xóa</button>
            <?php echo form_close(); ?>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
        <tr><td colspan="11" class="text-center text-muted py-4">Chưa có sản phẩm kho nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="<?php echo base_url('assets/js/image-lightbox.js'); ?>"></script>
