<?php
  // Nhóm nguyên liệu theo danh mục để hiển thị dạng optgroup cho dễ tìm.
  $items_by_category = array();
  foreach ($items as $it)
  {
    $items_by_category[$it['category_name']][] = $it;
  }
?>
<div class="container py-3 py-md-4" style="max-width:760px;">
  <a href="<?php echo site_url('me/recipes'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Công thức pha chế</a>
  <h4 class="fw-bold mb-3">Công thức — <?php echo htmlspecialchars($recipe['name']); ?></h4>

  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <?php echo form_open(current_url(), array('enctype' => 'multipart/form-data')); ?>
        <div class="mb-3">
          <label class="form-label">Hình ảnh</label>
          <div class="d-flex align-items-center gap-3">
            <img id="recipeImagePreview" src="<?php echo $recipe['image'] ? base_url('assets/'.$recipe['image']) : ''; ?>"
                 class="rounded border <?php echo $recipe['image'] ? '' : 'd-none'; ?>" style="width:88px;height:88px;object-fit:cover;">
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control" onchange="papHandleImageInput(this, 'recipeImagePreview', 'imageStatus');">
          </div>
          <div class="form-text">Ảnh JPG/PNG/WEBP. Ảnh lớn (chụp từ điện thoại) sẽ tự động được nén nhỏ lại. Hiện trên thẻ công thức ở /pha-che. Để trống nếu không đổi ảnh.</div>
          <div id="imageStatus" class="form-text text-primary"></div>
        </div>
        <div class="row g-2 align-items-end mb-2">
          <div class="col-sm-7">
            <label class="form-label">Tên món</label>
            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($recipe['name']); ?>">
          </div>
          <div class="col-sm-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select">
              <option value="ACTIVE" <?php echo $recipe['status']==='ACTIVE'?'selected':''; ?>>Hoạt động</option>
              <option value="INACTIVE" <?php echo $recipe['status']==='INACTIVE'?'selected':''; ?>>Ẩn</option>
            </select>
          </div>
          <div class="col-sm-2">
            <button class="btn btn-brand w-100">Lưu</button>
          </div>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-sm-4">
            <label class="form-label">Thành phẩm ra (sơ chế)</label>
            <input type="number" step="0.001" min="0" name="yield_quantity" class="form-control" placeholder="VD: 1000" value="<?php echo $recipe['yield_quantity'] !== NULL ? rtrim(rtrim(number_format($recipe['yield_quantity'], 3, '.', ''), '0'), '.') : ''; ?>">
          </div>
          <div class="col-sm-3">
            <label class="form-label">Đơn vị</label>
            <select name="yield_unit" class="form-select">
              <option value="">-- Chọn --</option>
              <?php foreach ($valid_units as $u): ?>
                <option value="<?php echo $u; ?>" <?php echo $recipe['yield_unit']===$u?'selected':''; ?>><?php echo $u; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-5">
            <div class="form-text mb-0">Điền nếu món này dùng làm thành phần (sơ chế) trong công thức khác — VD: mẻ Trà Lài ra 1000ml.</div>
          </div>
        </div>
        <div class="mt-2">
          <label class="form-label">Cách làm (mỗi dòng 1 bước)</label>
          <textarea name="instructions" class="form-control" rows="4" placeholder="VD:&#10;Đun sôi nước&#10;Cho trà vào ủ 10 phút&#10;Lọc bã, để nguội"><?php echo htmlspecialchars($recipe['instructions'] ?: ''); ?></textarea>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <?php
    $steps = $recipe['instructions'] ? array_filter(array_map('trim', explode("\n", $recipe['instructions']))) : array();
  ?>
  <?php if ( ! empty($steps)): ?>
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <h6 class="fw-bold mb-2"><i class="bi bi-list-ol"></i> Cách làm</h6>
      <ol class="mb-0">
        <?php foreach ($steps as $step): ?>
          <li><?php echo htmlspecialchars($step); ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-list-ul"></i> Thành phần</h6>

      <?php if ($has_missing_cost): ?>
        <div class="alert alert-warning py-2 small">
          Một số thành phần bên dưới chưa đủ dữ liệu giá (nguyên liệu chưa cấu hình "Giá / đơn vị cơ sở", hoặc công thức con chưa cấu hình "Thành phẩm ra") nên Tổng cost sẽ chưa chính xác.
          <a href="<?php echo site_url('me/inventory/items'); ?>">Cập nhật giá nguyên liệu</a>.
        </div>
      <?php endif; ?>

      <div class="table-responsive mb-3">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light"><tr><th>Thành phần</th><th class="text-end">Định lượng</th><th class="text-end">Cost</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($ingredients as $ing): ?>
            <tr>
              <td>
                <?php echo htmlspecialchars($ing['item_name']); ?>
                <?php if ($ing['source_recipe_id']): ?>
                  <span class="badge bg-info-subtle text-info-emphasis ms-1">sơ chế</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php echo rtrim(rtrim(number_format($ing['quantity'], 3, '.', ''), '0'), '.'); ?>
                <?php echo htmlspecialchars($ing['unit_label'] ?: $ing['item_unit_name']); ?>
              </td>
              <td class="text-end">
                <?php if ($ing['cost'] !== NULL): ?>
                  <?php echo money_format_vnd($ing['cost']); ?>
                <?php else: ?>
                  <span class="text-muted small">Chưa có giá</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php echo form_open('me/recipes/'.$recipe['id'].'/ingredients/'.$ing['id'].'/delete', array('class' => 'd-inline', 'onsubmit' => "return confirm('Xóa thành phần này?');")); ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                <?php echo form_close(); ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($ingredients)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có thành phần nào.</td></tr>
          <?php endif; ?>
          </tbody>
          <?php if ( ! empty($ingredients)): ?>
          <tfoot>
            <tr class="fw-bold border-top">
              <td colspan="2">Tổng cost</td>
              <td class="text-end text-brand"><?php echo money_format_vnd($total_cost); ?></td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>

      <hr>
      <h6 class="fw-bold mb-3">Thêm thành phần</h6>
      <?php echo form_open('me/recipes/'.$recipe['id'].'/ingredients/add', array('class' => 'row g-2 align-items-end')); ?>
        <div class="col-sm-7">
          <label class="form-label">Nguyên liệu / Công thức (sơ chế)</label>
          <select name="ingredient_ref" class="form-select" required>
            <option value="">-- Chọn thành phần --</option>
            <?php if ( ! empty($other_recipes)): ?>
              <optgroup label="Công thức khác (sơ chế)">
                <?php foreach ($other_recipes as $r): ?>
                  <option value="recipe:<?php echo $r['id']; ?>">
                    <?php echo htmlspecialchars($r['name']); ?>
                    <?php if ($r['yield_quantity'] === NULL): ?> (chưa cấu hình thành phẩm ra)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
            <?php foreach ($items_by_category as $category_name => $group): ?>
              <optgroup label="<?php echo htmlspecialchars($category_name); ?>">
                <?php foreach ($group as $it): ?>
                  <option value="item:<?php echo $it['id']; ?>">
                    <?php echo htmlspecialchars($it['name']); ?>
                    <?php if ($it['base_unit'] && $it['base_unit_cost'] !== NULL): ?>
                      (<?php echo money_format_vnd($it['base_unit_cost']); ?>/<?php echo htmlspecialchars($it['base_unit']); ?>)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label">Định lượng</label>
          <input type="number" step="0.001" min="0" name="quantity" class="form-control" placeholder="VD: 50" required>
        </div>
        <div class="col-sm-2">
          <button class="btn btn-outline-brand w-100"><i class="bi bi-plus-lg"></i> Thêm</button>
        </div>
      <?php echo form_close(); ?>
      <div class="form-text">Định lượng tính theo đơn vị cơ sở (ml/g/cái/lát/lá) nếu đã cấu hình, ngược lại tính theo đơn vị tính thông thường. Chọn "Công thức khác" để dùng 1 công thức sơ chế làm thành phần.</div>
    </div>
  </div>
</div>

<script src="<?php echo base_url('assets/js/image-compress.js'); ?>"></script>
