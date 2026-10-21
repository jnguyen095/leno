<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Món</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/products/import'); ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-up"></i> Import Excel</a>
      <a href="<?php echo site_url('me/products/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm</a>
    </div>
  </div>
  <?php // Bộ lọc: danh mục + trạng thái tự lọc khi đổi; ô tìm kiếm lọc khi bấm Enter / nút Lọc. ?>
  <?php echo form_open('me/products', array('method' => 'get', 'class' => 'card border-0 shadow-sm rounded-4 mb-3', 'id' => 'productFilter')); ?>
    <div class="card-body row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="search" name="q" class="form-control" placeholder="Tìm theo tên hoặc SKU…" value="<?php echo htmlspecialchars($filters['q']); ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="category_id" class="form-select" onchange="this.form.submit()">
          <option value="">Tất cả danh mục</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo (int) $filters['category_id'] === (int) $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto">
        <div class="btn-group" role="group" aria-label="Trạng thái">
          <?php foreach (array('' => 'Tất cả', 'ACTIVE' => 'Đang bán', 'INACTIVE' => 'Đã ẩn') as $value => $label): $rid = 'status_'.($value ?: 'ALL'); ?>
            <input type="radio" class="btn-check" name="status" id="<?php echo $rid; ?>" value="<?php echo $value; ?>"
                   <?php echo $filters['status'] === $value ? 'checked' : ''; ?> onchange="this.form.submit()">
            <label class="btn btn-outline-brand" for="<?php echo $rid; ?>"><?php echo $label; ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-md-auto d-flex gap-2">
        <button class="btn btn-brand"><i class="bi bi-funnel"></i> Lọc</button>
        <?php if ($filters['q'] !== '' || $filters['category_id'] || $filters['status'] !== ''): ?>
          <a href="<?php echo site_url('me/products'); ?>" class="btn btn-outline-secondary">Xoá lọc</a>
        <?php endif; ?>
      </div>
    </div>
  <?php echo form_close(); ?>
  <div class="small text-muted mb-2"><?php echo count($products); ?> sản phẩm</div>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>Ảnh</th><th>SKU</th><th>Tên món</th><th>Danh mục</th><th>Kho</th><th class="text-end">Giá bán</th><th class="text-end">Giá vốn</th><th class="text-end">Lãi / món</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <?php if ($p['image']): ?>
              <img src="<?php echo base_url('assets/'.$p['image']); ?>" style="width:48px;height:48px;object-fit:cover;" class="rounded border">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted" style="width:48px;height:48px;"><i class="bi bi-cup-straw"></i></div>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($p['sku']); ?></td>
          <td><?php echo htmlspecialchars($p['product_name']); ?></td>
          <td><?php echo htmlspecialchars($p['category_name']); ?></td>
          <td>
            <?php if ($p['track_inventory']): ?>
              <span class="badge bg-info text-dark" title="<?php echo htmlspecialchars((string) $p['inventory_category_name']); ?>">Quản lý kho<?php if ($p['inventory_category_name']): ?> · <?php echo htmlspecialchars($p['inventory_category_name']); ?><?php endif; ?></span>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td class="text-end"><?php echo money_format_vnd($p['price']); ?></td>
          <?php // Giá vốn không bắt buộc: chưa nhập thì để gạch; lãi âm (bán lỗ) tô đỏ. ?>
          <?php if ($p['cost_price'] !== NULL): $profit = (float) $p['price'] - (float) $p['cost_price']; ?>
            <td class="text-end text-muted"><?php echo money_format_vnd($p['cost_price']); ?></td>
            <td class="text-end text-nowrap fw-semibold <?php echo $profit < 0 ? 'text-danger' : 'text-success'; ?>"><?php echo money_format_vnd($profit); ?>
              <?php if ((float) $p['price'] > 0): ?><div class="small fw-normal"><?php echo round($profit / (float) $p['price'] * 100); ?>%</div><?php endif; ?></td>
          <?php else: ?>
            <td class="text-end text-muted small" colspan="2" data-bs-toggle="tooltip" title="Bấm Sửa để nhập giá vốn">chưa có giá vốn</td>
          <?php endif; ?>
          <td><span class="badge bg-<?php echo $p['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $p['status']; ?></span></td>
          <td>
            <a href="<?php echo site_url('me/products/'.$p['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa món"><i class="bi bi-pencil"></i></a>
            <?php echo form_open('me/products/'.$p['id'].'/delete', array('class'=>'d-inline', 'data-confirm' => html_escape('Ẩn món "'.$p['product_name'].'"?'), 'data-confirm-message' => html_escape('Món bị ẩn sẽ không hiện trên thực đơn POS.'), 'data-confirm-ok' => 'Ẩn')); ?>
              <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Ẩn món"><i class="bi bi-eye-slash"></i></button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($products)): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">Không có sản phẩm nào phù hợp.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
