<?php
  // Báo cáo danh mục: như Báo cáo hàng hóa nhưng gộp theo danh mục món. Bấm tên danh mục -> Báo cáo hàng hóa của danh mục đó.
  // Biến: $period, $ranges, $sort, $dir, $rows, $totals, $reports, $can_products.
  $money = function ($v) { return number_format(round((float) $v), 0, ',', '.'); };
  $period_q = $period['range'] === 'custom' ? array('from' => $period['from_date'], 'to' => $period['to_date']) : array('range' => $period['range']);
  $url = function ($path, array $extra) use ($period_q, $sort, $dir) {
    return site_url($path).'?'.http_build_query(array_merge($period_q, array('sort' => $sort, 'dir' => $dir), $extra));
  };
  $sort_link = function ($key, $label) use ($sort, $dir, $url) {
    $next = $sort === $key && $dir === 'desc' ? 'asc' : 'desc';
    $icon = $sort === $key ? ($dir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill') : 'bi-chevron-expand opacity-50';
    return '<a class="rp-sort" href="'.htmlspecialchars($url('me/reports/categories', array('sort' => $key, 'dir' => $next))).'">'.$label.' <i class="bi '.$icon.'"></i></a>';
  };
  // Link sang Báo cáo hàng hóa lọc theo danh mục, giữ nguyên khoảng thời gian.
  $drill = function ($r) use ($can_products, $period_q) {
    if ( ! $can_products || ! $r['category_id']) return htmlspecialchars($r['category_name']);
    return '<a href="'.htmlspecialchars(site_url('me/reports/products').'?'.http_build_query($period_q + array('category_id' => $r['category_id']))).'" class="fw-semibold text-body"'
      .' data-bs-toggle="tooltip" title="Xem từng món trong danh mục">'.htmlspecialchars($r['category_name']).' <i class="bi bi-chevron-right small text-muted"></i></a>';
  };
  $profit_cell = function ($r) use ($money) {
    if ($r['profit'] === NULL) return '<span class="text-muted small" data-bs-toggle="tooltip" title="Các món trong danh mục chưa có giá vốn — nhập ở Quản trị → Món">chưa có giá vốn</span>';
    $html = '<span class="fw-semibold '.($r['profit'] < 0 ? 'text-danger' : 'text-success').'">'.$money($r['profit']).'</span>';
    if ($r['margin'] !== NULL) $html .= '<div class="small text-muted">'.$r['margin'].'%'
      .($r['qty_no_cost'] ? ' <i class="bi bi-info-circle text-warning" data-bs-toggle="tooltip" title="'.$r['qty_no_cost'].' món bán lúc chưa có giá vốn — lãi chưa tính phần này"></i>' : '').'</div>';
    return $html;
  };
  $share_bar = function ($r) { return '<div class="rp-share" title="'.$r['share'].'% doanh thu"><span style="width:'.min(100, $r['share']).'%"></span></div>'; };
?>
<div class="container-xl py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <?php if (count($reports) > 1): ?>
        <a href="<?php echo site_url('me/reports'); ?>" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Báo cáo</a>
      <?php endif; ?>
      <h4 class="fw-bold mb-1"><i class="bi bi-collection"></i> Báo cáo danh mục</h4>
      <div class="text-muted small">Đơn đã thanh toán từ <b><?php echo $period['label']; ?></b> (tính theo giờ thanh toán).</div>
    </div>
    <a href="<?php echo htmlspecialchars($url('me/reports/categories-export', array())); ?>" class="btn btn-outline-success"
       <?php echo $rows ? '' : 'aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:.5"'; ?>><i class="bi bi-file-earmark-excel"></i> Xuất Excel</a>
  </div>

  <?php // ---------- Bộ lọc thời gian ---------- ?>
  <div class="orders-card mb-3">
    <div class="rp-ranges mb-2">
      <?php foreach ($ranges as $key => $label): ?>
        <a href="<?php echo htmlspecialchars(site_url('me/reports/categories').'?'.http_build_query(array('range' => $key, 'sort' => $sort, 'dir' => $dir))); ?>"
           class="rp-chip <?php echo $period['range'] === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </div>
    <form method="get" action="<?php echo site_url('me/reports/categories'); ?>" class="row g-2 align-items-end">
      <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>"><input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="rpFrom">Từ ngày</label>
        <input type="date" name="from" id="rpFrom" class="form-control" value="<?php echo $period['from_date']; ?>" max="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="rpTo">Đến ngày</label>
        <input type="date" name="to" id="rpTo" class="form-control" value="<?php echo $period['to_date']; ?>" max="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="col-12 col-md-2 d-flex gap-2">
        <button class="btn btn-brand flex-fill"><i class="bi bi-funnel"></i> Xem</button>
        <?php if ($period['range'] !== 'month'): ?>
          <a href="<?php echo site_url('me/reports/categories'); ?>" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Về tháng này"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php // ---------- Tổng hợp ---------- ?>
  <div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-lg-3"><div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-collection"></i> Danh mục có bán</div><div class="dash-tile-value"><?php echo count($rows); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-cart-check"></i> Tổng số lượng</div><div class="dash-tile-value"><?php echo $money($totals['qty']); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-cash-stack"></i> Doanh thu</div><div class="dash-tile-value"><?php echo $money($totals['revenue']); ?><small>đ</small></div></div></div>
    <div class="col-6 col-lg-3"><div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-graph-up-arrow"></i> Lãi gộp</div>
      <?php if ($totals['profit'] === NULL): ?>
        <div class="dash-tile-value">—</div><span class="dash-sub">chưa có giá vốn</span>
      <?php else: ?>
        <div class="dash-tile-value <?php echo $totals['profit'] < 0 ? 'text-danger' : ''; ?>"><?php echo $money($totals['profit']); ?><small>đ</small></div>
        <span class="dash-sub">biên lãi <?php echo (int) $totals['margin']; ?>%<?php echo $totals['qty_no_cost'] ? ' · <span class="text-warning-emphasis">'.$money($totals['qty_no_cost']).' món chưa có giá vốn</span>' : ''; ?></span>
      <?php endif; ?>
    </div></div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="orders-card text-center text-muted py-5">
      <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
      Không có món nào được bán trong khoảng thời gian này.
    </div>
  <?php else: ?>
  <div class="orders-card">
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table rp-table">
        <thead><tr>
          <th style="width:56px">#</th>
          <th><?php echo $sort_link('name', 'Danh mục'); ?></th>
          <th class="text-end"><?php echo $sort_link('products', 'Số món đã bán'); ?></th>
          <th class="text-end"><?php echo $sort_link('qty', 'Số lượng bán'); ?></th>
          <th class="text-end" style="min-width:180px"><?php echo $sort_link('revenue', 'Doanh thu'); ?></th>
          <th class="text-end"><?php echo $sort_link('profit', 'Lãi gộp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td class="text-muted"><?php echo $i + 1; ?></td>
            <td><?php echo $drill($r); ?></td>
            <td class="text-end"><?php echo $r['products']; ?></td>
            <td class="text-end fw-semibold"><?php echo $money($r['qty']); ?></td>
            <td class="text-end"><?php echo $money($r['revenue']); ?><div class="d-flex align-items-center justify-content-end gap-2 small text-muted"><?php echo $share_bar($r); ?><?php echo $r['share']; ?>%</div></td>
            <td class="text-end text-nowrap"><?php echo $profit_cell($r); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="rp-total">
          <td colspan="2">Tổng cộng (<?php echo count($rows); ?> danh mục)</td>
          <td class="text-end"><?php echo array_sum(array_column($rows, 'products')); ?></td>
          <td class="text-end"><?php echo $money($totals['qty']); ?></td>
          <td class="text-end"><?php echo $money($totals['revenue']); ?></td>
          <td class="text-end"><?php echo $totals['profit'] === NULL ? '—' : $money($totals['profit']); ?></td>
        </tr></tfoot>
      </table>
    </div>
    <div class="d-md-none">
      <div class="rp-mobile-sort small text-muted mb-2">Sắp xếp:
        <?php echo $sort_link('revenue', 'Doanh thu'); ?> · <?php echo $sort_link('qty', 'Số lượng'); ?> · <?php echo $sort_link('profit', 'Lãi'); ?>
      </div>
      <?php foreach ($rows as $i => $r): ?>
        <div class="rp-item">
          <div class="d-flex justify-content-between gap-2">
            <div class="min-w-0"><div class="fw-semibold"><?php echo ($i + 1).'. '.$drill($r); ?></div>
              <div class="small text-muted"><?php echo $r['products']; ?> món · <?php echo $r['share']; ?>% doanh thu</div></div>
            <div class="text-end text-nowrap"><div class="fw-bold"><?php echo $money($r['qty']); ?></div><div class="small text-muted">đã bán</div></div>
          </div>
          <?php echo $share_bar($r); ?>
          <div class="rp-item-nums">
            <div><small>Doanh thu</small><b><?php echo $money($r['revenue']); ?>đ</b></div>
            <div class="text-end"><small>Lãi gộp</small><?php echo $profit_cell($r); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="rp-item rp-total">
        <div class="d-flex justify-content-between"><span>Tổng cộng (<?php echo count($rows); ?> danh mục)</span><span><?php echo $money($totals['qty']); ?></span></div>
        <div class="rp-item-nums"><div><small>Doanh thu</small><b><?php echo $money($totals['revenue']); ?>đ</b></div>
          <div class="text-end"><small>Lãi gộp</small><b><?php echo $totals['profit'] === NULL ? '—' : $money($totals['profit']).'đ'; ?></b></div></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i>
    Doanh thu: sau bảng giá và giảm giá (chia theo tỉ lệ từng món), chưa gồm VAT. Lãi gộp = doanh thu − giá vốn, chỉ tính các lần bán đã có giá vốn.
    <?php if ($can_products): ?>Bấm tên danh mục để xem từng món.<?php endif; ?>
  </div>
</div>
