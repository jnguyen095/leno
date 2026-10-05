<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Lịch sử nhập/xuất kho</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/stock/in'); ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-box-arrow-in-down"></i> Nhập kho</a>
      <a href="<?php echo site_url('me/stock/out'); ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-up"></i> Xuất kho</a>
      <a href="<?php echo site_url('me/stock/adjust'); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-clipboard-check"></i> Kiểm kho</a>
    </div>
  </div>
  <?php echo form_open('me/stock/history', array('method' => 'get', 'class' => 'row g-2 align-items-center mb-3')); ?>
    <div class="col-auto">
      <label class="small text-muted mb-0 me-1">Từ ngày</label>
      <input type="date" name="date_from" value="<?php echo htmlspecialchars((string) $date_from); ?>" class="form-control form-control-sm d-inline-block" style="width:auto;">
    </div>
    <div class="col-auto">
      <label class="small text-muted mb-0 me-1">Đến ngày</label>
      <input type="date" name="date_to" value="<?php echo htmlspecialchars((string) $date_to); ?>" class="form-control form-control-sm d-inline-block" style="width:auto;">
    </div>
    <div class="col-auto">
      <select name="created_by" class="form-select form-select-sm">
        <option value="">Tất cả người thực hiện</option>
        <?php foreach ($users as $u): ?>
          <option value="<?php echo $u['id']; ?>" <?php echo (string) $created_by === (string) $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['fullname']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="type" class="form-select form-select-sm">
        <option value="">Tất cả loại</option>
        <option value="IN" <?php echo $type === 'IN' ? 'selected' : ''; ?>>Nhập</option>
        <option value="OUT" <?php echo $type === 'OUT' ? 'selected' : ''; ?>>Xuất</option>
        <option value="ADJUST" <?php echo $type === 'ADJUST' ? 'selected' : ''; ?>>Kiểm kho</option>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-sm btn-brand">Lọc</button>
      <?php if ($date_from || $date_to || $created_by || $type): ?>
        <a href="<?php echo site_url('me/stock/history'); ?>" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
      <?php endif; ?>
    </div>
  <?php echo form_close(); ?>

  <div class="small text-muted mb-2">Tổng <?php echo $total; ?> lô nhập/xuất.</div>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light">
        <tr><th></th><th>Thời gian</th><th>Loại</th><th>Số sản phẩm</th><th class="text-end">Tổng SL</th><th>Điểm xuất</th><th>Ghi chú</th><th>Người thực hiện</th></tr>
      </thead>
      <tbody>
      <?php foreach ($batches as $b): $collapse_id = 'batch-'.preg_replace('/[^a-zA-Z0-9_-]/', '', $b['batch_id']); ?>
        <tr class="batch-row" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapse_id; ?>">
          <td class="text-muted"><i class="bi bi-chevron-down"></i></td>
          <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></td>
          <td>
            <?php if ($b['type'] === 'IN'): ?>
              <span class="badge bg-success">Nhập<?php if ($b['source'] === 'EXCEL'): ?> (file)<?php endif; ?></span>
            <?php elseif ($b['type'] === 'OUT'): ?>
              <span class="badge bg-danger">Xuất</span>
            <?php else: ?>
              <span class="badge bg-primary">Kiểm kho</span>
            <?php endif; ?>
          </td>
          <td><?php echo (int) $b['item_count']; ?> sản phẩm</td>
          <td class="text-end"><?php echo ($b['type'] === 'ADJUST' && $b['total_qty'] > 0 ? '+' : '').rtrim(rtrim(number_format($b['total_qty'], 2, '.', ''), '0'), '.'); ?></td>
          <td><?php echo $b['dispense_point_name'] ? htmlspecialchars($b['dispense_point_name']) : '—'; ?></td>
          <td class="text-muted small"><?php echo htmlspecialchars((string) $b['note']); ?></td>
          <td><?php echo htmlspecialchars((string) $b['created_by_name']); ?></td>
        </tr>
        <tr class="collapse" id="<?php echo $collapse_id; ?>">
          <td></td>
          <td colspan="7" class="p-0">
            <table class="table table-sm mb-0 bg-light">
              <thead>
                <tr class="text-muted small"><th>STT</th><th>Sản phẩm</th><th class="text-end">Số lượng</th></tr>
              </thead>
              <tbody>
              <?php $line_stt = 1; foreach ($b['lines'] as $l): ?>
                <tr>
                  <td><?php echo $line_stt++; ?></td>
                  <td><?php echo htmlspecialchars($l['item_name']); ?></td>
                  <td class="text-end"><?php echo ($b['type'] === 'ADJUST' && $l['qty'] > 0 ? '+' : '').rtrim(rtrim(number_format($l['qty'], 2, '.', ''), '0'), '.'); ?> <?php echo htmlspecialchars($l['unit']); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($batches)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có giao dịch nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
    <?php
      $page_qs = array();
      if ($date_from) $page_qs['date_from'] = $date_from;
      if ($date_to) $page_qs['date_to'] = $date_to;
      if ($created_by) $page_qs['created_by'] = $created_by;
      if ($type) $page_qs['type'] = $type;

      $window = 2;
      $pages_to_show = array();
      for ($p = 1; $p <= $total_pages; $p++)
      {
          if ($p === 1 || $p === $total_pages || ($p >= $page - $window && $p <= $page + $window))
          {
              $pages_to_show[] = $p;
          }
      }
    ?>
    <nav class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="small text-muted">Trang <?php echo $page; ?>/<?php echo $total_pages; ?> (tối đa <?php echo $per_page; ?> lô mỗi trang)</span>
      <ul class="pagination pagination-sm mb-0 flex-wrap">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo site_url('me/stock/history').'?'.http_build_query(array_merge($page_qs, array('page' => $page - 1))); ?>">‹</a>
        </li>
        <?php $prev_p = 0; foreach ($pages_to_show as $p): ?>
          <?php if ($prev_p && $p - $prev_p > 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo site_url('me/stock/history').'?'.http_build_query(array_merge($page_qs, array('page' => $p))); ?>"><?php echo $p; ?></a>
          </li>
          <?php $prev_p = $p; ?>
        <?php endforeach; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo site_url('me/stock/history').'?'.http_build_query(array_merge($page_qs, array('page' => $page + 1))); ?>">›</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.batch-row').forEach(function(row){
  var target = document.querySelector(row.getAttribute('data-bs-target'));
  var icon = row.querySelector('td i');
  if ( ! target || ! icon) return;
  target.addEventListener('shown.bs.collapse', function(){ icon.className = icon.className.replace('bi-chevron-down', 'bi-chevron-up'); });
  target.addEventListener('hidden.bs.collapse', function(){ icon.className = icon.className.replace('bi-chevron-up', 'bi-chevron-down'); });
});
</script>
