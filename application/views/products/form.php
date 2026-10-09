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
        <?php if ($product): ?>
          <input type="text" name="sku" class="form-control" required value="<?php echo htmlspecialchars($product['sku']); ?>">
        <?php else: ?>
          <?php // Thêm mới: SKU tự sinh theo danh mục (CPE-01, CPE-02...), vẫn cho sửa tay. ?>
          <div class="input-group">
            <input type="text" name="sku" id="skuInput" class="form-control" value="<?php echo htmlspecialchars($suggested_sku); ?>">
            <button type="button" class="btn btn-outline-secondary d-none" id="skuAutoBtn" onclick="useAutoSku()" title="Dùng lại mã tự động">
              <i class="bi bi-arrow-repeat"></i> Tự động
            </button>
          </div>
          <input type="hidden" name="sku_auto" id="skuAuto" value="1">
          <div class="form-text" id="skuHint">Tự tạo theo danh mục, số chính xác được cấp khi lưu.</div>
        <?php endif; ?>
      </div>
      <div class="col-6">
        <label class="form-label">Danh mục</label>
        <select name="category_id" id="categorySelect" class="form-select" required>
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
<script src="<?php echo asset_url('assets/js/image-compress.js'); ?>"></script>
<?php if ( ! $product): ?>
<script>
// SKU tự động: đổi danh mục -> lấy SKU kế tiếp; gõ tay -> giữ mã tự nhập (bấm "Tự động" để quay lại).
(function(){
  var input = document.getElementById('skuInput');
  var autoFlag = document.getElementById('skuAuto');
  var autoBtn = document.getElementById('skuAutoBtn');
  var hint = document.getElementById('skuHint');
  var select = document.getElementById('categorySelect');

  function setAuto(on){
    autoFlag.value = on ? '1' : '0';
    autoBtn.classList.toggle('d-none', on);
    hint.textContent = on ? 'Tự tạo theo danh mục, số chính xác được cấp khi lưu.' : 'Đang dùng mã tự nhập.';
  }

  function refreshSku(){
    if (autoFlag.value !== '1') return;
    fetch('<?php echo base_url('me/products/next-sku'); ?>?category_id=' + encodeURIComponent(select.value))
      .then(function(r){ return r.json(); })
      .then(function(res){ if (autoFlag.value === '1' && res.sku) input.value = res.sku; })
      .catch(function(){});
  }

  window.useAutoSku = function(){ setAuto(true); refreshSku(); };
  input.addEventListener('input', function(){ setAuto(input.value.trim() === ''); });
  select.addEventListener('change', refreshSku);
  refreshSku();   // danh mục được chọn sẵn có thể khác danh mục đầu tiên (vd trình duyệt nhớ lựa chọn)
})();
</script>
<?php endif; ?>
