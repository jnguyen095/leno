<div class="container py-3 py-md-4" style="max-width:560px;">
  <h4 class="fw-bold mb-3"><?php echo $page_title; ?></h4>
  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
  <?php echo form_open(current_url(), array('enctype' => 'multipart/form-data')); ?>
    <div class="mb-3">
      <label class="form-label">Hình ảnh sản phẩm</label>
      <div class="d-flex align-items-center gap-3">
        <img id="imagePreview" src="<?php echo ($item && $item['image']) ? base_url('assets/'.$item['image']) : ''; ?>"
             class="rounded border <?php echo ($item && $item['image']) ? '' : 'd-none'; ?>" style="width:88px;height:88px;object-fit:cover;cursor:zoom-in;" onclick="papOpenImageLightbox(this);">
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control" onchange="papHandleImageInput(this, 'imagePreview', 'imageStatus');">
      </div>
      <div class="form-text">Ảnh JPG/PNG/WEBP. Ảnh lớn (chụp từ điện thoại) sẽ tự động được nén nhỏ lại. Để trống nếu không đổi ảnh.</div>
      <div id="imageStatus" class="form-text text-primary"></div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-sm-6">
        <label class="form-label">SKU</label>
        <input type="text" name="sku" id="skuInput" class="form-control" placeholder="Để trống sẽ tự sinh" value="<?php echo $item ? htmlspecialchars($item['sku']) : ''; ?>">
        <?php if ( ! $item): ?><div class="form-text">Bỏ trống để hệ thống tự sinh SKU theo danh mục, hoặc tự nhập.</div><?php endif; ?>
      </div>
      <div class="col-sm-6">
        <label class="form-label">Danh mục</label>
        <select name="category_id" id="categorySelect" class="form-select" required>
          <option value="">-- Chọn danh mục --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo ($item && (int) $item['category_id'] === (int) $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Tên sản phẩm</label>
      <input type="text" name="name" class="form-control" required value="<?php echo $item ? htmlspecialchars($item['name']) : ''; ?>">
    </div>
    <div class="row g-3 mb-3">
      <div class="col-sm-4">
        <label class="form-label">Đơn vị tính</label>
        <select name="unit_id" class="form-select" required>
          <option value="">-- Chọn --</option>
          <?php foreach ($units as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php echo ($item && (int) $item['unit_id'] === (int) $u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Chưa có đơn vị cần dùng? <a href="<?php echo site_url('me/inventory/units/create'); ?>" target="_blank">Thêm mới</a>.</div>
      </div>
      <div class="col-sm-4">
        <label class="form-label">Bảo quản</label>
        <select name="storage_type" class="form-select">
          <option value="DRY" <?php echo (!$item || $item['storage_type']==='DRY') ? 'selected' : ''; ?>>Khô</option>
          <option value="COLD" <?php echo ($item && $item['storage_type']==='COLD') ? 'selected' : ''; ?>>Lạnh</option>
        </select>
      </div>
      <div class="col-sm-4">
        <label class="form-label">Ngưỡng cảnh báo</label>
        <input type="number" step="0.01" min="0" name="low_stock_threshold" class="form-control" value="<?php echo $item ? $item['low_stock_threshold'] : 0; ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-sm-6">
        <label class="form-label">Đơn vị cơ sở (để tính công thức)</label>
        <select name="base_unit" id="baseUnitSelect" class="form-select" onchange="toggleBaseUnitCost();">
          <option value="">-- Không dùng --</option>
          <option value="ml" <?php echo ($item && $item['base_unit']==='ml') ? 'selected' : ''; ?>>ml</option>
          <option value="g" <?php echo ($item && $item['base_unit']==='g') ? 'selected' : ''; ?>>gram</option>
          <option value="cái" <?php echo ($item && $item['base_unit']==='cái') ? 'selected' : ''; ?>>cái</option>
          <option value="lát" <?php echo ($item && $item['base_unit']==='lát') ? 'selected' : ''; ?>>lát</option>
          <option value="lá" <?php echo ($item && $item['base_unit']==='lá') ? 'selected' : ''; ?>>lá</option>
          <option value="ly" <?php echo ($item && $item['base_unit']==='ly') ? 'selected' : ''; ?>>ly</option>
          <option value="gói" <?php echo ($item && $item['base_unit']==='gói') ? 'selected' : ''; ?>>gói</option>
        </select>
        <div class="form-text">Chỉ cần khi nguyên liệu này dùng trong công thức pha chế (VD: 1000ML/Chai → ml, ống hút → 1 cái, chanh → 1 lát).</div>
      </div>
      <div class="col-sm-6" id="baseUnitCostWrap">
        <label class="form-label">Giá / đơn vị cơ sở (đ)</label>
        <input type="number" step="0.01" min="0" name="base_unit_cost" class="form-control" value="<?php echo ($item && $item['base_unit_cost'] !== NULL) ? rtrim(rtrim(number_format($item['base_unit_cost'], 4, '.', ''), '0'), '.') : ''; ?>">
        <div class="form-text">VD: chai 1000ml giá 200.000đ → nhập 200 (đ/ml).</div>
      </div>
    </div>
    <?php if ($item): ?>
    <div class="mb-3">
      <label class="form-label">Trạng thái</label>
      <select name="status" class="form-select">
        <option value="ACTIVE" <?php echo $item['status']==='ACTIVE'?'selected':''; ?>>Hoạt động</option>
        <option value="INACTIVE" <?php echo $item['status']==='INACTIVE'?'selected':''; ?>>Ẩn</option>
      </select>
    </div>
    <div class="mb-3 text-muted small">
      Tồn kho hiện tại: <strong><?php echo $item['qty_on_hand']; ?> <?php echo htmlspecialchars($item['unit_name']); ?></strong>
      — chỉnh qua màn <a href="<?php echo site_url('me/stock/in'); ?>">Nhập kho</a> / <a href="<?php echo site_url('me/stock/out'); ?>">Xuất kho</a>, không sửa trực tiếp ở đây.
    </div>
    <?php endif; ?>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg">Lưu</button>
      <a href="<?php echo site_url('me/inventory/items'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>

<script src="<?php echo asset_url('assets/js/image-compress.js'); ?>"></script>
<script src="<?php echo asset_url('assets/js/image-lightbox.js'); ?>"></script>
<script>
function toggleBaseUnitCost(){
  var has = document.getElementById('baseUnitSelect').value !== '';
  document.getElementById('baseUnitCostWrap').classList.toggle('d-none', !has);
}
toggleBaseUnitCost();
</script>

<?php if ( ! $item): ?>
<script>
(function(){
  var skuInput = document.getElementById('skuInput');
  var categorySelect = document.getElementById('categorySelect');
  var autoFilled = true; // SKU đang trống -> lần đổi danh mục đầu tiên sẽ tự điền gợi ý

  skuInput.addEventListener('input', function(){
    autoFilled = false; // người dùng đã tự gõ -> không ghi đè nữa
  });

  categorySelect.addEventListener('change', function(){
    if ( ! autoFilled) return;
    var categoryId = categorySelect.value;
    if ( ! categoryId){ skuInput.value = ''; return; }
    fetch('<?php echo site_url('me/inventory/items/next-sku'); ?>?category_id=' + categoryId)
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (autoFilled && res.sku){
          skuInput.value = res.sku;
          skuInput.dataset.auto = '1';
        }
      });
  });
})();
</script>
<?php endif; ?>
