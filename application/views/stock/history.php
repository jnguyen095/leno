<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Lịch sử nhập/xuất kho</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/stock/in'); ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-box-arrow-in-down"></i> Nhập kho</a>
      <a href="<?php echo site_url('me/stock/out'); ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-up"></i> Xuất kho</a>
      <a href="<?php echo site_url('me/stock/adjust'); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-clipboard-check"></i> Kiểm kho</a>
      <?php if ($current_user['role'] === 'ADMIN'): ?>
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#purgePanel" aria-expanded="false">
          <i class="bi bi-trash3"></i> Dọn dẹp lịch sử cũ
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if ($current_user['role'] === 'ADMIN' && $purge_options): ?>
  <?php // Dọn dẹp (giống Nhật ký hệ thống): xoá lịch sử cũ hơn N tháng; tồn kho hiện tại không đổi. ?>
  <div class="collapse" id="purgePanel">
    <div class="purge-card mb-3">
      <div class="fw-semibold mb-1"><i class="bi bi-trash3 text-danger"></i> Xoá lịch sử nhập/xuất kho cũ</div>
      <div class="small text-muted mb-3">Xoá vĩnh viễn các phiếu nhập / xuất / kiểm kho trước mốc đã chọn để nhẹ dữ liệu. <b>Tồn kho hiện tại không thay đổi.</b></div>
      <?php echo form_open('me/stock/history/purge', array('id' => 'purgeForm', 'data-confirm' => 'Xoá lịch sử cũ?', 'data-confirm-ok' => 'Xoá vĩnh viễn')); ?>
        <div class="purge-options">
          <?php foreach ($purge_options as $o): ?>
            <input type="radio" class="btn-check" name="months" value="<?php echo $o['months']; ?>" id="purge<?php echo $o['months']; ?>"
                   data-count="<?php echo $o['batches']; ?>" data-lines="<?php echo $o['lines']; ?>" data-before="<?php echo date('d/m/Y', strtotime($o['before'])); ?>" <?php echo $o['months'] === 3 ? 'checked' : ''; ?>>
            <label class="purge-option" for="purge<?php echo $o['months']; ?>">
              <b>Cũ hơn <?php echo $o['months'] === 12 ? '1 năm' : $o['months'].' tháng'; ?></b>
              <small>trước <?php echo date('d/m/Y', strtotime($o['before'])); ?></small>
              <span class="purge-count <?php echo $o['batches'] ? '' : 'is-zero'; ?>"><?php echo number_format($o['batches'], 0, ',', '.'); ?> phiếu</span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-end mt-3">
          <button class="btn btn-danger" id="purgeBtn"><i class="bi bi-trash3"></i> <span>Xoá</span></button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
  <script>
  (function(){
    var form = document.getElementById('purgeForm'), btn = document.getElementById('purgeBtn');
    var fmt = function(n){ return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    function sync(){
      var r = form.querySelector('input[name="months"]:checked'), n = parseInt(r.dataset.count, 10), lines = parseInt(r.dataset.lines, 10);
      btn.querySelector('span').textContent = n ? 'Xoá ' + fmt(n) + ' phiếu' : 'Không có gì để xoá';
      btn.disabled = n === 0;
      form.dataset.confirm = 'Xoá vĩnh viễn ' + fmt(n) + ' phiếu (' + fmt(lines) + ' dòng) trước ngày ' + r.dataset.before + '?';
      form.dataset.confirmMessage = 'Không khôi phục được. Tồn kho hiện tại không thay đổi.';
    }
    form.querySelectorAll('input[name="months"]').forEach(function(r){ r.addEventListener('change', sync); });
    sync();
  })();
  </script>
  <?php endif; ?>
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
