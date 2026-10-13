<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-journal-text"></i> Nhật ký hệ thống</h4>
    <?php if ($current_user['role'] === 'ADMIN'): ?>
      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#purgePanel" aria-expanded="false">
        <i class="bi bi-trash3"></i> Dọn dẹp nhật ký cũ
      </button>
    <?php endif; ?>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if ($current_user['role'] === 'ADMIN'): ?>
  <?php // Dọn dẹp: xoá nhật ký cũ hơn N tháng (chọn mốc, xem trước số bản ghi, xác nhận bằng hộp thoại). ?>
  <div class="collapse" id="purgePanel">
    <div class="purge-card mb-3">
      <div class="fw-semibold mb-1"><i class="bi bi-trash3 text-danger"></i> Xoá nhật ký cũ</div>
      <div class="small text-muted mb-3">Xoá vĩnh viễn các bản ghi trước mốc đã chọn để nhẹ dữ liệu. Lịch sử báo bếp của các đơn còn đang mở luôn được giữ lại.</div>
      <?php echo form_open('me/audit-logs/purge', array('id' => 'purgeForm', 'data-confirm' => 'Xoá nhật ký cũ?', 'data-confirm-ok' => 'Xoá vĩnh viễn')); ?>
        <div class="purge-options">
          <?php foreach ($purge_options as $i => $o): ?>
            <input type="radio" class="btn-check" name="months" value="<?php echo $o['months']; ?>" id="purge<?php echo $o['months']; ?>"
                   data-count="<?php echo $o['count']; ?>" data-before="<?php echo date('d/m/Y', strtotime($o['before'])); ?>" <?php echo $o['months'] === 3 ? 'checked' : ''; ?>>
            <label class="purge-option" for="purge<?php echo $o['months']; ?>">
              <b>Cũ hơn <?php echo $o['months'] === 12 ? '1 năm' : $o['months'].' tháng'; ?></b>
              <small>trước <?php echo date('d/m/Y', strtotime($o['before'])); ?></small>
              <span class="purge-count <?php echo $o['count'] ? '' : 'is-zero'; ?>"><?php echo number_format($o['count'], 0, ',', '.'); ?> bản ghi</span>
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
  // Nút + nội dung hộp xác nhận theo mốc đang chọn; không có gì để xoá thì khoá nút.
  (function(){
    var form = document.getElementById('purgeForm'), btn = document.getElementById('purgeBtn');
    function sync(){
      var r = form.querySelector('input[name="months"]:checked'), n = parseInt(r.dataset.count, 10);
      var fmt = n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      btn.querySelector('span').textContent = n ? 'Xoá ' + fmt + ' bản ghi' : 'Không có gì để xoá';
      btn.disabled = n === 0;
      form.dataset.confirm = 'Xoá vĩnh viễn ' + fmt + ' bản ghi trước ngày ' + r.dataset.before + '?';
      form.dataset.confirmMessage = 'Không khôi phục được. Lịch sử báo bếp của các đơn còn đang mở vẫn được giữ.';
    }
    form.querySelectorAll('input[name="months"]').forEach(function(r){ r.addEventListener('change', sync); });
    sync();
  })();
  </script>
  <?php endif; ?>

  <?php echo form_open('me/audit-logs', array('method' => 'get', 'class' => 'row g-2 align-items-center mb-3')); ?>
    <div class="col-auto">
      <label class="small text-muted mb-0 me-1">Từ ngày</label>
      <input type="date" name="date_from" value="<?php echo htmlspecialchars((string) $date_from); ?>" class="form-control form-control-sm d-inline-block" style="width:auto;">
    </div>
    <div class="col-auto">
      <label class="small text-muted mb-0 me-1">Đến ngày</label>
      <input type="date" name="date_to" value="<?php echo htmlspecialchars((string) $date_to); ?>" class="form-control form-control-sm d-inline-block" style="width:auto;">
    </div>
    <div class="col-auto">
      <select name="module" class="form-select form-select-sm">
        <option value="">Tất cả khu vực</option>
        <?php foreach ($modules as $m): ?>
          <option value="<?php echo htmlspecialchars($m); ?>" <?php echo (string) $module === (string) $m ? 'selected' : ''; ?>><?php echo htmlspecialchars(audit_module_label($m)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="action" class="form-select form-select-sm">
        <option value="">Tất cả hành động</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?php echo htmlspecialchars($a); ?>" <?php echo (string) $action === (string) $a ? 'selected' : ''; ?>><?php echo htmlspecialchars($a); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="created_by" class="form-select form-select-sm">
        <option value="">Tất cả người dùng</option>
        <?php foreach ($users as $u): ?>
          <option value="<?php echo $u['id']; ?>" <?php echo (string) $created_by === (string) $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['fullname']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-sm btn-brand">Lọc</button>
      <?php if ($date_from || $date_to || $module || $action || $created_by): ?>
        <a href="<?php echo site_url('me/audit-logs'); ?>" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
      <?php endif; ?>
    </div>
  <?php echo form_close(); ?>

  <div class="small text-muted mb-2">Tổng <?php echo $total; ?> bản ghi.</div>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light">
        <tr><th></th><th>Thời gian</th><th>Khu vực</th><th>Hành động</th><th>Người thực hiện</th></tr>
      </thead>
      <tbody>
      <?php foreach ($logs as $log): $collapse_id = 'log-'.$log['id']; $has_detail = $log['old_data'] || $log['new_data']; ?>
        <tr class="<?php echo $has_detail ? 'log-row' : ''; ?>" style="<?php echo $has_detail ? 'cursor:pointer;' : ''; ?>" <?php echo $has_detail ? 'data-bs-toggle="collapse" data-bs-target="#'.$collapse_id.'"' : ''; ?>>
          <td class="text-muted"><?php if ($has_detail): ?><i class="bi bi-chevron-down"></i><?php endif; ?></td>
          <td class="text-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
          <td><?php echo htmlspecialchars(audit_module_label($log['module'])); ?></td>
          <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['action']); ?></span></td>
          <td><?php echo $log['user_fullname'] ? htmlspecialchars($log['user_fullname']) : '<span class="text-muted">Hệ thống</span>'; ?></td>
        </tr>
        <?php if ($has_detail): ?>
        <tr class="collapse" id="<?php echo $collapse_id; ?>">
          <td></td>
          <td colspan="4" class="bg-light">
            <div class="row g-2 py-2">
              <?php if ($log['old_data']): ?>
              <div class="col-md-6">
                <div class="small text-muted mb-1">Trước khi thay đổi</div>
                <pre class="small bg-white border rounded p-2 mb-0" style="white-space:pre-wrap;"><?php echo htmlspecialchars(json_encode(json_decode($log['old_data'], TRUE), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
              </div>
              <?php endif; ?>
              <?php if ($log['new_data']): ?>
              <div class="col-md-6">
                <div class="small text-muted mb-1">Sau khi thay đổi</div>
                <pre class="small bg-white border rounded p-2 mb-0" style="white-space:pre-wrap;"><?php echo htmlspecialchars(json_encode(json_decode($log['new_data'], TRUE), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
              </div>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Chưa có bản ghi nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
    <?php
      $page_qs = array();
      if ($date_from) $page_qs['date_from'] = $date_from;
      if ($date_to) $page_qs['date_to'] = $date_to;
      if ($module) $page_qs['module'] = $module;
      if ($action) $page_qs['action'] = $action;
      if ($created_by) $page_qs['created_by'] = $created_by;

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
      <span class="small text-muted">Trang <?php echo $page; ?>/<?php echo $total_pages; ?> (tối đa <?php echo $per_page; ?> bản ghi mỗi trang)</span>
      <ul class="pagination pagination-sm mb-0 flex-wrap">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo site_url('me/audit-logs').'?'.http_build_query(array_merge($page_qs, array('page' => $page - 1))); ?>">‹</a>
        </li>
        <?php $prev_p = 0; foreach ($pages_to_show as $p): ?>
          <?php if ($prev_p && $p - $prev_p > 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo site_url('me/audit-logs').'?'.http_build_query(array_merge($page_qs, array('page' => $p))); ?>"><?php echo $p; ?></a>
          </li>
          <?php $prev_p = $p; ?>
        <?php endforeach; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo site_url('me/audit-logs').'?'.http_build_query(array_merge($page_qs, array('page' => $page + 1))); ?>">›</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.log-row').forEach(function(row){
  var target = document.querySelector(row.getAttribute('data-bs-target'));
  var icon = row.querySelector('td i');
  if ( ! target || ! icon) return;
  target.addEventListener('shown.bs.collapse', function(){ icon.className = icon.className.replace('bi-chevron-down', 'bi-chevron-up'); });
  target.addEventListener('hidden.bs.collapse', function(){ icon.className = icon.className.replace('bi-chevron-up', 'bi-chevron-down'); });
});
</script>
