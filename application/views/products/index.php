<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Sản phẩm</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/products/import'); ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-up"></i> Import Excel</a>
      <a href="<?php echo site_url('me/products/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>Ảnh</th><th>SKU</th><th>Tên</th><th>Danh mục</th><th>Kho</th><th class="text-end">Giá</th><th>Trạng thái</th><th></th></tr></thead>
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
          <td><span class="badge bg-<?php echo $p['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $p['status']; ?></span></td>
          <td>
            <a href="<?php echo site_url('me/products/'.$p['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
            <?php echo form_open('me/products/'.$p['id'].'/delete', array('class'=>'d-inline', 'onsubmit'=>"return confirm('Ẩn sản phẩm này?');")); ?>
              <button class="btn btn-sm btn-outline-danger">Ẩn</button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
