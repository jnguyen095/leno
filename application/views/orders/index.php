<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Danh sách đơn hàng</h4>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php
    // Giữ khoảng ngày + bàn đang xem khi chuyển tab trạng thái/trang, và ngược lại — các bộ lọc độc lập nhau.
    // Luôn truyền date_from/date_to cả khi rỗng (chế độ "xem tất cả ngày"), nếu không controller sẽ
    // hiểu nhầm là chưa lọc gì và tự động quay về mặc định hôm nay.
    $date_qs = array('date_from' => $date_from, 'date_to' => $date_to);
    if ($table_id) $date_qs['table_id'] = $table_id;
  ?>

  <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
    <div class="btn-group btn-group-sm">
      <a href="<?php echo site_url('me/orders').'?'.http_build_query($date_qs); ?>" class="btn btn-outline-secondary <?php echo ! $status ? 'active' : ''; ?>">Tất cả</a>
      <a href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($date_qs, array('status' => 'OPEN'))); ?>" class="btn btn-outline-primary <?php echo $status==='OPEN' ? 'active' : ''; ?>">Đang mở</a>
      <a href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($date_qs, array('status' => 'WAIT_PAYMENT'))); ?>" class="btn btn-outline-warning <?php echo $status==='WAIT_PAYMENT' ? 'active' : ''; ?>">Chờ TT</a>
      <a href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($date_qs, array('status' => 'PAID'))); ?>" class="btn btn-outline-success <?php echo $status==='PAID' ? 'active' : ''; ?>">Đã TT</a>
    </div>

    <?php echo form_open('me/orders', array('method' => 'get', 'class' => 'd-flex gap-2 align-items-center flex-wrap')); ?>
      <?php if ($status): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>"><?php endif; ?>
      <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control form-control-sm" style="max-width:150px;">
      <span class="small text-muted">đến</span>
      <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control form-control-sm" style="max-width:150px;">
      <select name="table_id" class="form-select form-select-sm" style="max-width:160px;">
        <option value="">Tất cả bàn</option>
        <?php foreach ($tables as $t): ?>
          <option value="<?php echo $t['id']; ?>" <?php echo (string) $table_id === (string) $t['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($t['table_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-brand">Lọc</button>
      <a href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($status ? array('status' => $status) : array(), $table_id ? array('table_id' => $table_id) : array(), array('date_from' => '', 'date_to' => ''))); ?>" class="btn btn-sm btn-outline-secondary">Xem tất cả ngày</a>
    <?php echo form_close(); ?>
  </div>

  <div class="small text-muted mb-3">
    <?php if ($date_from && $date_to && $date_from === $date_to): ?>
      Đơn hàng ngày <?php echo date('d/m/Y', strtotime($date_from)); ?>
    <?php elseif ($date_from || $date_to): ?>
      Đơn hàng từ <?php echo $date_from ? date('d/m/Y', strtotime($date_from)) : '...'; ?> đến <?php echo $date_to ? date('d/m/Y', strtotime($date_to)) : '...'; ?>
    <?php else: ?>
      Đơn hàng tất cả các ngày
    <?php endif; ?>
    <?php if ($table_id):
      foreach ($tables as $t) { if ((string) $t['id'] === (string) $table_id) { echo '— bàn '.htmlspecialchars($t['table_name']); break; } }
    endif; ?>
    — tổng <?php echo $total; ?> đơn
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle bg-white rounded shadow-sm">
      <thead class="table-light">
        <tr><th>Mã đơn</th><th>Bàn</th><th class="text-end">Tổng tiền</th><th>Thanh toán</th><th>Người tạo</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?php echo htmlspecialchars($o['order_no']); ?></td>
          <td><?php echo $o['table_name'] ? htmlspecialchars($o['table_name']) : '<span class="badge bg-secondary"><i class="bi bi-bag-check"></i> Mang đi</span>'; ?></td>
          <td class="text-end"><?php echo money_format_vnd($o['total_amount']); ?></td>
          <td><?php echo $o['payment_method'] ? htmlspecialchars(payment_method_label($o['payment_method'])) : '<span class="text-muted">—</span>'; ?></td>
          <td><?php echo $o['created_by_name'] ? htmlspecialchars($o['created_by_name']) : '<span class="text-muted">—</span>'; ?></td>
          <td><span class="badge bg-<?php echo order_status_badge($o['status']); ?>"><?php echo $o['status']; ?></span></td>
          <td class="small text-muted"><?php echo date('d/m H:i', strtotime($o['created_at'])); ?></td>
          <td class="text-nowrap">
            <a href="<?php echo site_url('me/orders/'.$o['id']); ?>" class="btn btn-sm btn-outline-primary">Xem</a>
            <?php if ($current_user['role'] === 'ADMIN'): ?>
              <?php echo form_open('me/orders/'.$o['id'].'/delete', array('class' => 'd-inline', 'onsubmit' => "return confirm('Xoá hẳn đơn ".htmlspecialchars($o['order_no'], ENT_QUOTES)."? Món, phiếu bếp và thanh toán của đơn cũng bị xoá, không khôi phục được.');")); ?>
                <input type="hidden" name="back_qs" value="<?php echo htmlspecialchars((string) $this->input->server('QUERY_STRING')); ?>">
                <button class="btn btn-sm btn-outline-danger" title="Xoá đơn"><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Không có đơn hàng nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
    <?php
      $page_qs = $date_qs;
      if ($status) $page_qs['status'] = $status;

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
      <span class="small text-muted">Trang <?php echo $page; ?>/<?php echo $total_pages; ?> (<?php echo count($orders); ?>/<?php echo $per_page; ?> đơn mỗi trang)</span>
      <ul class="pagination pagination-sm mb-0 flex-wrap">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($page_qs, array('page' => $page - 1))); ?>">‹</a>
        </li>
        <?php $prev_p = 0; foreach ($pages_to_show as $p): ?>
          <?php if ($prev_p && $p - $prev_p > 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($page_qs, array('page' => $p))); ?>"><?php echo $p; ?></a>
          </li>
          <?php $prev_p = $p; ?>
        <?php endforeach; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($page_qs, array('page' => $page + 1))); ?>">›</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>
