<div class="container py-3 py-md-4" style="max-width:560px;">
  <h4 class="fw-bold mb-3"><?php echo $page_title; ?></h4>
  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
  <?php echo form_open(current_url(), array('enctype' => 'multipart/form-data')); ?>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Hình ảnh sản phẩm</label>
        <div class="d-flex align-items-center gap-3">
          <img id="imagePreview" src="<?php echo ($product && $product['image']) ? base_url('assets/'.$product['image']) : ''; ?>"
               class="rounded border <?php echo ($product && $product['image']) ? '' : 'd-none'; ?>" style="width:88px;height:88px;object-fit:cover;">
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control" onchange="papHandleImageInput(this, 'imagePreview', 'imageStatus');">
        </div>
        <div class="form-text">Ảnh JPG/PNG/WEBP. Ảnh lớn (chụp từ điện thoại) sẽ tự động được nén nhỏ lại. Để trống nếu không đổi ảnh.</div>
        <div id="imageStatus" class="form-text text-primary"></div>
      </div>
      <div class="col-6">
        <label class="form-label">Mã SKU</label>
        <input type="text" name="sku" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['sku']) : ''; ?>">
      </div>
      <div class="col-6">
        <label class="form-label">Danh mục</label>
        <select name="category_id" class="form-select" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo ($product && $product['category_id']==$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Tên sản phẩm</label>
        <input type="text" name="product_name" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['product_name']) : ''; ?>">
      </div>
      <div class="col-6">
        <label class="form-label">Giá bán (đ)</label>
        <input type="number" name="price" class="form-control" required min="0" step="1000" value="<?php echo $product ? (int)$product['price'] : ''; ?>">
      </div>
      <?php if ($product): ?>
      <div class="col-6">
        <label class="form-label">Trạng thái</label>
        <select name="status" class="form-select">
          <option value="ACTIVE" <?php echo $product['status']==='ACTIVE'?'selected':''; ?>>Đang bán</option>
          <option value="INACTIVE" <?php echo $product['status']==='INACTIVE'?'selected':''; ?>>Ngừng bán</option>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-12">
        <label class="form-label">Mô tả</label>
        <textarea name="description" class="form-control" rows="2"><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
      </div>
      <div class="col-12"><hr class="my-1"></div>
      <div class="col-6">
        <label class="form-label">Danh mục kho liên kết</label>
        <select name="inventory_category_id" class="form-select">
          <option value="">-- Không liên kết --</option>
          <?php foreach ($inventory_categories as $ic): ?>
            <option value="<?php echo $ic['id']; ?>" <?php echo ($product && (int) $product['inventory_category_id'] === (int) $ic['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ic['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Nhóm vật tư kho mà món này thuộc về (không tự trừ tồn kho khi bán).</div>
      </div>
      <div class="col-6 d-flex align-items-end">
        <div class="form-check">
          <input type="checkbox" name="track_inventory" value="1" class="form-check-input" id="trackInventory" <?php echo ($product && $product['track_inventory']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="trackInventory">Quản lý kho</label>
          <div class="form-text">Đánh dấu nếu cần theo dõi tồn kho cho món này.</div>
        </div>
      </div>
    </div>
    <div class="d-grid gap-2 mt-4">
      <button class="btn btn-brand btn-lg">Lưu</button>
      <a href="<?php echo site_url('me/products'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
<script src="<?php echo base_url('assets/js/image-compress.js'); ?>"></script>
