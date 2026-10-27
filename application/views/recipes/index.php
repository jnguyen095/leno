<?php
  // Công thức pha chế: danh sách + chọn nhiều công thức để xuất Excel in dán tường cho nhân viên (Recipes::export).
  // Ô chọn dùng thuộc tính form="recipeExportForm" — không lồng form (mỗi dòng đã có form Xoá riêng).
?>
<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-egg-fried"></i> Công thức pha chế</h4>
    <a href="<?php echo site_url('me/recipes/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm công thức</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if ($recipes): ?>
    <div class="small text-muted mb-2"><i class="bi bi-printer"></i> Tích chọn các công thức cần in rồi bấm <b>Xuất Excel để in</b> (mở file → In, khổ A4) để dán cho nhân viên.</div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr>
        <th style="width:44px"><input type="checkbox" class="form-check-input" id="rcAll" aria-label="Chọn tất cả công thức" data-bs-toggle="tooltip" title="Chọn tất cả"></th>
        <th>Món</th><th class="text-end">Tổng cost</th><th>Trạng thái</th><th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($recipes as $r): ?>
        <tr>
          <td><input type="checkbox" class="form-check-input rc-pick" name="ids[]" value="<?php echo $r['id']; ?>" form="recipeExportForm" aria-label="Chọn <?php echo htmlspecialchars($r['name']); ?>"></td>
          <td><a href="<?php echo site_url('me/recipes/'.$r['id'].'/edit'); ?>" class="fw-semibold text-decoration-none"><?php echo htmlspecialchars($r['name']); ?></a></td>
          <td class="text-end fw-semibold text-brand">
            <?php echo ! $r['cost_complete'] ? '<span class="text-warning-emphasis" title="Chưa đủ dữ liệu giá">~ </span>' : ''; ?><?php echo money_format_vnd($r['total_cost']); ?>
          </td>
          <td><span class="badge bg-<?php echo $r['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $r['status']==='ACTIVE'?'Hoạt động':'Ẩn'; ?></span></td>
          <td class="text-nowrap">
            <a href="<?php echo site_url('me/recipes/'.$r['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa công thức"><i class="bi bi-pencil"></i></a>
            <?php echo form_open('me/recipes/'.$r['id'].'/delete', array('class' => 'd-inline', 'data-confirm' => html_escape('Xoá công thức "'.$r['name'].'"?'), 'data-confirm-message' => html_escape('Toàn bộ thành phần của công thức cũng bị xoá.'))); ?>
              <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá công thức"><i class="bi bi-trash"></i></button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recipes)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Chưa có công thức nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($recipes): ?>
  <?php // Thanh xuất Excel: luôn hiện dưới cùng, bật nút khi đã chọn ít nhất 1 công thức. ?>
  <form method="get" action="<?php echo site_url('me/recipes/export'); ?>" id="recipeExportForm" class="rc-export-bar">
    <span class="small"><b id="rcCount">0</b> công thức đã chọn</span>
    <label class="form-check mb-0 small">
      <input type="checkbox" class="form-check-input" name="instructions" value="1"> Kèm cách làm
    </label>
    <label class="form-check mb-0 small">
      <input type="checkbox" class="form-check-input" name="per_page" value="1"> Mỗi công thức một trang
    </label>
    <button class="btn btn-success" id="rcExportBtn" disabled><i class="bi bi-file-earmark-excel"></i> Xuất Excel để in</button>
  </form>
  <?php endif; ?>
</div>

<?php if ($recipes): ?>
<script>
(function(){
  var all = document.getElementById('rcAll'), picks = document.querySelectorAll('.rc-pick');
  var count = document.getElementById('rcCount'), btn = document.getElementById('rcExportBtn');
  function update(){
    var n = Array.prototype.filter.call(picks, function(c){ return c.checked; }).length;
    count.textContent = n; btn.disabled = n === 0;
    all.checked = n === picks.length; all.indeterminate = n > 0 && n < picks.length;
  }
  all.addEventListener('change', function(){ picks.forEach(function(c){ c.checked = all.checked; }); update(); });
  picks.forEach(function(c){ c.addEventListener('change', update); });
  update();
})();
</script>
<?php endif; ?>
