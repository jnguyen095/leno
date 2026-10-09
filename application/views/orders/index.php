<?php
  // Danh sách đơn hàng: thẻ tổng quan + tab trạng thái (kèm số đơn) + bộ lọc (thu gọn trên điện thoại)
  // + bảng (máy tính) / danh sách thẻ (điện thoại) + phân trang.
  $payment_options = array('CASH' => 'Tiền mặt', 'TRANSFER' => 'Chuyển khoản', 'CARD' => 'Thẻ', 'QR' => 'QR Pay', 'NONE' => 'Chưa thanh toán');
  $payment_icons = array('CASH' => 'bi-cash-coin', 'TRANSFER' => 'bi-bank', 'CARD' => 'bi-credit-card', 'QR' => 'bi-qr-code');
  $status_meta = array(
    'OPEN'      => array('Đang phục vụ', 'open'),
    'PAID'      => array('Đã thanh toán', 'paid'),
    'CANCELLED' => array('Đã hủy', 'cancelled'),
  );

  // Giữ các bộ lọc khi đổi tab trạng thái / trang. Luôn truyền date_from/date_to (kể cả rỗng = mọi ngày),
  // nếu không controller hiểu là chưa lọc và quay về hôm nay.
  $base_qs = array('date_from' => $date_from, 'date_to' => $date_to);
  if ($table_id) $base_qs['table_id'] = $table_id;
  if ($payment_method) $base_qs['payment_method'] = $payment_method;
  if ($created_by) $base_qs['created_by'] = $created_by;
  $url = function ($extra = array()) use ($base_qs) { return site_url('me/orders').'?'.http_build_query(array_merge($base_qs, $extra)); };

  // Số liệu theo trạng thái (cùng bộ lọc, mọi trạng thái).
  $count_of = function ($s) use ($summary) { return isset($summary[$s]) ? $summary[$s]['count'] : 0; };
  $all_count = array_sum(array_map(function ($r) { return $r['count']; }, $summary));
  $revenue = isset($summary['PAID']) ? $summary['PAID']['total'] : 0;
  $open_total = isset($summary['OPEN']) ? $summary['OPEN']['total'] : 0;

  // Nút chọn nhanh khoảng ngày.
  $today = date('Y-m-d');
  $presets = array(
    'Hôm nay'   => array($today, $today),
    'Hôm qua'   => array(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))),
    '7 ngày'    => array(date('Y-m-d', strtotime('-6 days')), $today),
    'Tháng này' => array(date('Y-m-01'), $today),
    'Tất cả'    => array('', ''),
  );
  $range_label = ! $date_from && ! $date_to ? 'Tất cả các ngày'
    : ($date_from === $date_to ? 'Ngày '.date('d/m/Y', strtotime($date_from))
    : ($date_from ? date('d/m/Y', strtotime($date_from)) : '…').' – '.($date_to ? date('d/m/Y', strtotime($date_to)) : '…'));
  $extra_filters = (int) (bool) $table_id + (int) (bool) $payment_method + (int) (bool) $created_by;
  $is_admin = $current_user['role'] === 'ADMIN';
  $export_qs = (string) $this->input->server('QUERY_STRING');
?>
<div class="container-fluid py-3 py-md-4 orders-page">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1">Đơn hàng</h4>
      <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo $range_label; ?></div>
    </div>
    <?php // Xuất đúng bộ lọc đang xem (giữ nguyên query string), không phân trang. ?>
    <a href="<?php echo site_url('me/orders/export').($export_qs !== '' ? '?'.htmlspecialchars($export_qs) : ''); ?>"
       class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> <span class="d-none d-sm-inline">Xuất </span>Excel</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php // ---------- Tổng quan ---------- ?>
  <div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-lg-3">
      <div class="orders-stat"><span class="orders-stat-icon bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></span>
        <div><div class="orders-stat-label">Doanh thu</div><div class="orders-stat-value"><?php echo money_format_vnd($revenue); ?></div></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="orders-stat"><span class="orders-stat-icon bg-primary-subtle text-primary"><i class="bi bi-check2-circle"></i></span>
        <div><div class="orders-stat-label">Đã thanh toán</div><div class="orders-stat-value"><?php echo $count_of('PAID'); ?> <small>đơn</small></div></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="orders-stat"><span class="orders-stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></span>
        <div><div class="orders-stat-label">Đang phục vụ</div><div class="orders-stat-value"><?php echo $count_of('OPEN'); ?> <small>đơn</small></div></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="orders-stat"><span class="orders-stat-icon bg-info-subtle text-info-emphasis"><i class="bi bi-receipt"></i></span>
        <div><div class="orders-stat-label">Chưa thu (đang phục vụ)</div><div class="orders-stat-value"><?php echo money_format_vnd($open_total); ?></div></div></div>
    </div>
  </div>

  <div class="orders-card">
    <?php // ---------- Tab trạng thái + nút bộ lọc (điện thoại) ---------- ?>
    <div class="orders-toolbar">
      <nav class="orders-tabs">
        <a href="<?php echo $url(); ?>" class="<?php echo ! $status ? 'active' : ''; ?>">Tất cả <span><?php echo $all_count; ?></span></a>
        <a href="<?php echo $url(array('status' => 'OPEN')); ?>" class="<?php echo $status === 'OPEN' ? 'active' : ''; ?>">Đang phục vụ <span><?php echo $count_of('OPEN'); ?></span></a>
        <a href="<?php echo $url(array('status' => 'PAID')); ?>" class="<?php echo $status === 'PAID' ? 'active' : ''; ?>">Đã thanh toán <span><?php echo $count_of('PAID'); ?></span></a>
        <?php if ($count_of('CANCELLED')): ?>
        <a href="<?php echo $url(array('status' => 'CANCELLED')); ?>" class="<?php echo $status === 'CANCELLED' ? 'active' : ''; ?>">Đã hủy <span><?php echo $count_of('CANCELLED'); ?></span></a>
        <?php endif; ?>
      </nav>
      <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#orderFilters">
        <i class="bi bi-funnel"></i> Bộ lọc<?php if ($extra_filters): ?> <span class="badge rounded-pill bg-brand"><?php echo $extra_filters; ?></span><?php endif; ?>
      </button>
    </div>

    <?php // ---------- Bộ lọc ---------- ?>
    <div class="collapse d-lg-block" id="orderFilters">
      <div class="orders-filters">
        <div class="orders-presets">
          <?php foreach ($presets as $label => $range): $on = $date_from === $range[0] && $date_to === $range[1]; ?>
            <a href="<?php echo $url(array_merge($status ? array('status' => $status) : array(), array('date_from' => $range[0], 'date_to' => $range[1]))); ?>"
               class="orders-chip <?php echo $on ? 'active' : ''; ?>"><?php echo $label; ?></a>
          <?php endforeach; ?>
        </div>
        <?php echo form_open('me/orders', array('method' => 'get', 'class' => 'row g-2 align-items-end')); ?>
          <?php if ($status): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>"><?php endif; ?>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label">Từ ngày</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control form-control-sm">
          </div>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label">Đến ngày</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control form-control-sm">
          </div>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label">Bàn</label>
            <select name="table_id" class="form-select form-select-sm">
              <option value="">Tất cả bàn</option>
              <?php foreach ($tables as $t): ?>
                <option value="<?php echo $t['id']; ?>" <?php echo (string) $table_id === (string) $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['table_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label">Thanh toán</label>
            <select name="payment_method" class="form-select form-select-sm">
              <option value="">Tất cả</option>
              <?php foreach ($payment_options as $pm => $pm_label): ?>
                <option value="<?php echo $pm; ?>" <?php echo $payment_method === $pm ? 'selected' : ''; ?>><?php echo $pm_label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 col-xl-2">
            <label class="form-label">Người tạo</label>
            <select name="created_by" class="form-select form-select-sm">
              <option value="">Tất cả</option>
              <?php foreach ($creators as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo (int) $created_by === (int) $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['fullname']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 col-xl-2 d-flex gap-2">
            <button class="btn btn-sm btn-brand flex-grow-1"><i class="bi bi-funnel"></i> Lọc</button>
            <?php if ($extra_filters): ?>
              <a href="<?php echo site_url('me/orders').'?'.http_build_query(array_merge($status ? array('status' => $status) : array(), array('date_from' => $date_from, 'date_to' => $date_to))); ?>"
                 class="btn btn-sm btn-outline-secondary" title="Bỏ lọc bàn / thanh toán / người tạo"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>

    <?php // ---------- Danh sách: bảng (máy tính) ---------- ?>
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table">
        <thead>
          <tr><th>Mã đơn</th><th>Bàn</th><th>Người tạo</th><th>Thanh toán</th><th>Trạng thái</th><th class="text-end">Tổng tiền</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): $sm = isset($status_meta[$o['status']]) ? $status_meta[$o['status']] : array($o['status'], 'cancelled'); ?>
          <tr class="orders-row" data-href="<?php echo site_url('me/orders/'.$o['id']); ?>">
            <td>
              <a href="<?php echo site_url('me/orders/'.$o['id']); ?>" class="fw-semibold text-body text-decoration-none"><?php echo htmlspecialchars($o['order_no']); ?></a>
              <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></div>
            </td>
            <td><?php echo $o['table_name'] ? htmlspecialchars($o['table_name']) : '<span class="orders-takeaway"><i class="bi bi-bag-check"></i> Mang đi</span>'; ?></td>
            <td><?php echo $o['created_by_name'] ? htmlspecialchars($o['created_by_name']) : '<span class="text-muted">—</span>'; ?></td>
            <td>
              <?php if ($o['payment_method']): ?>
                <span class="text-nowrap"><i class="bi <?php echo isset($payment_icons[$o['payment_method']]) ? $payment_icons[$o['payment_method']] : 'bi-wallet2'; ?> text-muted"></i> <?php echo htmlspecialchars(payment_method_label($o['payment_method'])); ?></span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td><span class="orders-status orders-status-<?php echo $sm[1]; ?>"><?php echo $sm[0]; ?></span></td>
            <td class="text-end fw-semibold text-nowrap"><?php echo money_format_vnd($o['total_amount']); ?></td>
            <td class="text-end text-nowrap">
              <a href="<?php echo site_url('me/orders/'.$o['id']); ?>" class="btn btn-sm btn-light" title="Xem đơn"><i class="bi bi-eye"></i></a>
              <?php if ($o['status'] === 'PAID'): ?>
                <a href="<?php echo site_url('me/orders/'.$o['id'].'/invoice'); ?>" target="_blank" class="btn btn-sm btn-light" title="In hóa đơn"><i class="bi bi-printer"></i></a>
              <?php endif; ?>
              <?php if ($is_admin): ?>
                <?php echo form_open('me/orders/'.$o['id'].'/delete', array('class' => 'd-inline js-order-delete', 'data-no' => $o['order_no'])); ?>
                  <input type="hidden" name="back_qs" value="<?php echo htmlspecialchars($export_qs); ?>">
                  <button class="btn btn-sm btn-light text-danger" title="Xoá đơn"><i class="bi bi-trash"></i></button>
                <?php echo form_close(); ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php // ---------- Danh sách: thẻ (điện thoại) ---------- ?>
    <div class="d-md-none orders-list">
      <?php foreach ($orders as $o): $sm = isset($status_meta[$o['status']]) ? $status_meta[$o['status']] : array($o['status'], 'cancelled'); ?>
        <div class="orders-item">
          <a href="<?php echo site_url('me/orders/'.$o['id']); ?>" class="orders-item-main">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div class="fw-semibold"><?php echo $o['table_name'] ? htmlspecialchars($o['table_name']) : '<i class="bi bi-bag-check"></i> Mang đi'; ?></div>
              <div class="fw-bold text-nowrap"><?php echo money_format_vnd($o['total_amount']); ?></div>
            </div>
            <div class="small text-muted"><?php echo htmlspecialchars($o['order_no']); ?> · <?php echo date('d/m H:i', strtotime($o['created_at'])); ?></div>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-1 small">
              <span class="orders-status orders-status-<?php echo $sm[1]; ?>"><?php echo $sm[0]; ?></span>
              <?php if ($o['payment_method']): ?><span class="text-muted"><i class="bi <?php echo isset($payment_icons[$o['payment_method']]) ? $payment_icons[$o['payment_method']] : 'bi-wallet2'; ?>"></i> <?php echo htmlspecialchars(payment_method_label($o['payment_method'])); ?></span><?php endif; ?>
              <?php if ($o['created_by_name']): ?><span class="text-muted"><i class="bi bi-person"></i> <?php echo htmlspecialchars($o['created_by_name']); ?></span><?php endif; ?>
            </div>
          </a>
          <?php if ($is_admin): ?>
            <?php echo form_open('me/orders/'.$o['id'].'/delete', array('class' => 'js-order-delete', 'data-no' => $o['order_no'])); ?>
              <input type="hidden" name="back_qs" value="<?php echo htmlspecialchars($export_qs); ?>">
              <button class="btn btn-sm btn-light text-danger" title="Xoá đơn"><i class="bi bi-trash"></i></button>
            <?php echo form_close(); ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($orders)): ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
        Không có đơn hàng nào<?php echo $date_from || $date_to ? ' trong khoảng ngày này' : ''; ?>.
      </div>
    <?php endif; ?>

    <?php // ---------- Phân trang ---------- ?>
    <?php if ($total_pages > 1): ?>
      <?php
        $page_qs = $status ? array('status' => $status) : array();
        $pages_to_show = array();
        for ($p = 1; $p <= $total_pages; $p++)
        {
            if ($p === 1 || $p === $total_pages || abs($p - $page) <= 2) $pages_to_show[] = $p;
        }
      ?>
      <nav class="orders-pager">
        <span class="small text-muted"><?php echo ($page - 1) * $per_page + 1; ?>–<?php echo ($page - 1) * $per_page + count($orders); ?> / <?php echo $total; ?> đơn</span>
        <ul class="pagination pagination-sm mb-0 flex-wrap">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $url(array_merge($page_qs, array('page' => $page - 1))); ?>">‹</a>
          </li>
          <?php $prev_p = 0; foreach ($pages_to_show as $p): ?>
            <?php if ($prev_p && $p - $prev_p > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo $url(array_merge($page_qs, array('page' => $p))); ?>"><?php echo $p; ?></a>
            </li>
            <?php $prev_p = $p; ?>
          <?php endforeach; ?>
          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $url(array_merge($page_qs, array('page' => $page + 1))); ?>">›</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>

<script>
// Điện thoại: dãy tab trạng thái trượt ngang -> cuộn để tab đang chọn luôn hiện.
(function(){
  var nav = document.querySelector('.orders-tabs'), on = nav && nav.querySelector('a.active');
  if (on && nav.scrollWidth > nav.clientWidth) nav.scrollLeft = on.offsetLeft - nav.offsetLeft - 16;
})();
// Bấm vào dòng (ngoài nút/link) để mở đơn.
document.querySelectorAll('.orders-row').forEach(function(tr){
  tr.addEventListener('click', function(e){
    if (e.target.closest('a, button, form')) return;
    window.location.href = tr.dataset.href;
  });
});
// Xoá đơn: xác nhận trước.
document.querySelectorAll('.js-order-delete').forEach(function(f){
  f.addEventListener('submit', function(e){
    if ( ! confirm('Xoá hẳn đơn ' + f.dataset.no + '? Món, phiếu bếp và thanh toán của đơn cũng bị xoá, không khôi phục được.')) e.preventDefault();
  });
});
</script>
