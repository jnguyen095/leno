<?php
  // Báo cáo hàng hóa: theo từng món — SKU, tên, số lượng bán, doanh thu, lãi gộp. Bảng trên máy tính, thẻ trên điện thoại.
  // Biến: $period, $ranges, $filters, $sort, $dir, $rows, $totals, $categories, $reports.
  $money = function ($v) { return number_format(round((float) $v), 0, ',', '.'); };
  // Giữ bộ lọc khi đổi khoảng thời gian / sắp xếp / xuất Excel.
  $base_q = array_filter(array('category_id' => $filters['category_id'] ?: NULL, 'q' => $filters['q'] !== '' ? $filters['q'] : NULL));
  $period_q = $period['range'] === 'custom' ? array('from' => $period['from_date'], 'to' => $period['to_date']) : array('range' => $period['range']);
  $url = function (array $extra) use ($base_q, $period_q, $sort, $dir) {
    $q = array_merge($base_q, $period_q, array('sort' => $sort, 'dir' => $dir), $extra);
    return site_url('me/reports/products').'?'.http_build_query(array_filter($q, function ($v) { return $v !== NULL && $v !== ''; }));
  };
  $sort_link = function ($key, $label, $class = '') use ($sort, $dir, $url) {
    $next = $sort === $key && $dir === 'desc' ? 'asc' : 'desc';
    $icon = $sort === $key ? ($dir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill') : 'bi-chevron-expand opacity-50';
    return '<a class="rp-sort '.$class.'" href="'.htmlspecialchars($url(array('sort' => $key, 'dir' => $next))).'">'.$label.' <i class="bi '.$icon.'"></i></a>';
  };
  $profit_cell = function ($r) use ($money) {
    if ($r['profit'] === NULL) return '<span class="text-muted small" data-bs-toggle="tooltip" title="Món chưa có giá vốn — nhập ở Quản trị → Món">chưa có giá vốn</span>';
    $html = '<span class="fw-semibold '.($r['profit'] < 0 ? 'text-danger' : 'text-success').'">'.$money($r['profit']).'</span>';
    if ($r['margin'] !== NULL) $html .= '<div class="small text-muted">'.$r['margin'].'%'
      .($r['qty_no_cost'] ? ' <i class="bi bi-info-circle text-warning" data-bs-toggle="tooltip" title="'.$r['qty_no_cost'].' món bán lúc chưa có giá vốn — lãi chưa tính phần này"></i>' : '').'</div>';
    return $html;
  };
?>
<div class="container-xl py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <?php if (count($reports) > 1): ?>
        <a href="<?php echo site_url('me/reports'); ?>" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Báo cáo</a>
      <?php endif; ?>
      <h4 class="fw-bold mb-1"><i class="bi bi-box-seam"></i> Báo cáo hàng hóa</h4>
      <div class="text-muted small">Đơn đã thanh toán từ <b><?php echo $period['label']; ?></b> (tính theo giờ thanh toán).</div>
    </div>
    <a href="<?php echo htmlspecialchars(str_replace('me/reports/products', 'me/reports/products-export', $url(array()))); ?>" class="btn btn-outline-success"
       <?php echo $rows ? '' : 'aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:.5"'; ?>><i class="bi bi-file-earmark-excel"></i> Xuất Excel</a>
  </div>

  <?php // ---------- Bộ lọc: khoảng thời gian nhanh, tự chọn ngày, danh mục, tìm món ---------- ?>
  <div class="orders-card mb-3">
    <div class="rp-ranges mb-2">
      <?php foreach ($ranges as $key => $label): ?>
        <a href="<?php echo htmlspecialchars(site_url('me/reports/products').'?'.http_build_query(array_merge($base_q, array('range' => $key, 'sort' => $sort, 'dir' => $dir)))); ?>"
           class="rp-chip <?php echo $period['range'] === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </div>
    <form method="get" action="<?php echo site_url('me/reports/products'); ?>" class="row g-2 align-items-end">
      <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>"><input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="rpFrom">Từ ngày</label>
        <input type="date" name="from" id="rpFrom" class="form-control" value="<?php echo $period['from_date']; ?>" max="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="rpTo">Đến ngày</label>
        <input type="date" name="to" id="rpTo" class="form-control" value="<?php echo $period['to_date']; ?>" max="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="rpCat">Danh mục</label>
        <select name="category_id" id="rpCat" class="form-select">
          <option value="">Tất cả danh mục</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo (int) $filters['category_id'] === (int) $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="rpQ">Tìm món</label>
        <input type="search" name="q" id="rpQ" class="form-control" placeholder="Tên hoặc SKU" value="<?php echo htmlspecialchars($filters['q']); ?>">
      </div>
      <div class="col-12 col-md-2 d-flex gap-2">
        <button class="btn btn-brand flex-fill"><i class="bi bi-funnel"></i> Xem</button>
        <?php if ($filters['q'] !== '' || $filters['category_id'] || $period['range'] !== 'month'): ?>
          <a href="<?php echo site_url('me/reports/products'); ?>" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Xoá bộ lọc"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php // ---------- Tổng hợp ---------- ?>
  <div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-lg-3"><div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-box-seam"></i> Số món đã bán</div><div class="dash-tile-value"><?php echo count($rows); ?></div></div></div>
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
      Không có món nào được bán trong khoảng thời gian này<?php echo $filters['q'] !== '' || $filters['category_id'] ? ' (theo bộ lọc đang chọn)' : ''; ?>.
    </div>
  <?php else: ?>
  <div class="orders-card">
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table rp-table">
        <thead><tr>
          <th style="width:56px">#</th>
          <th><?php echo $sort_link('sku', 'SKU'); ?></th>
          <th><?php echo $sort_link('name', 'Tên món'); ?></th>
          <th class="text-end"><?php echo $sort_link('qty', 'Số lượng bán'); ?></th>
          <th class="text-end"><?php echo $sort_link('revenue', 'Doanh thu'); ?></th>
          <th class="text-end"><?php echo $sort_link('profit', 'Lãi gộp'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td class="text-muted"><?php echo $i + 1; ?></td>
            <td class="small"><code><?php echo htmlspecialchars($r['sku']); ?></code></td>
            <td><div class="fw-semibold"><?php echo htmlspecialchars($r['product_name']); ?></div><div class="small text-muted"><?php echo htmlspecialchars((string) $r['category_name']); ?></div></td>
            <td class="text-end fw-semibold"><?php echo $money($r['qty']); ?></td>
            <td class="text-end"><?php echo $money($r['revenue']); ?></td>
            <td class="text-end text-nowrap"><?php echo $profit_cell($r); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="rp-total">
          <td colspan="3">Tổng cộng (<?php echo count($rows); ?> món)</td>
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
            <div class="min-w-0"><div class="fw-semibold"><?php echo ($i + 1).'. '.htmlspecialchars($r['product_name']); ?></div>
              <div class="small text-muted"><code><?php echo htmlspecialchars($r['sku']); ?></code> · <?php echo htmlspecialchars((string) $r['category_name']); ?></div></div>
            <div class="text-end text-nowrap"><div class="fw-bold"><?php echo $money($r['qty']); ?></div><div class="small text-muted">đã bán</div></div>
          </div>
          <div class="rp-item-nums">
            <div><small>Doanh thu</small><b><?php echo $money($r['revenue']); ?>đ</b></div>
            <div class="text-end"><small>Lãi gộp</small><?php echo $profit_cell($r); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="rp-item rp-total">
        <div class="d-flex justify-content-between"><span>Tổng cộng (<?php echo count($rows); ?> món)</span><span><?php echo $money($totals['qty']); ?></span></div>
        <div class="rp-item-nums"><div><small>Doanh thu</small><b><?php echo $money($totals['revenue']); ?>đ</b></div>
          <div class="text-end"><small>Lãi gộp</small><b><?php echo $totals['profit'] === NULL ? '—' : $money($totals['profit']).'đ'; ?></b></div></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i>
    Doanh thu: sau bảng giá và giảm giá (chia theo tỉ lệ từng món), chưa gồm VAT. Lãi gộp = doanh thu − giá vốn, chỉ tính các lần bán đã có giá vốn (giá vốn lúc bán).
  </div>
</div>
