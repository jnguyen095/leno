<?php
  // Sổ quỹ: thẻ tổng (đầu kỳ / thu / chi / cuối kỳ, tách Tiền mặt – Chuyển khoản) + bộ lọc + danh sách phiếu
  // (phiếu nhập tay + tiền bán hàng tự động) + hộp "Lập phiếu thu / chi" dùng chung (thêm / sửa).
  $money = function ($v) { return number_format(round((float) $v), 0, ',', '.'); };
  $qs = (string) $this->input->server('QUERY_STRING');
  $pm_icons = array('CASH' => 'bi-cash-coin', 'TRANSFER' => 'bi-bank');
  $base = $filters; unset($base['date_from'], $base['date_to']);
  $range_url = function ($key) use ($base) { return site_url('me/cashbook').'?'.http_build_query(array_merge($base, array('range' => $key))); };
  $ranges = array('today' => 'Hôm nay', '7d' => '7 ngày', 'month' => 'Tháng này', 'last_month' => 'Tháng trước');
  $range_text = empty($filters['date_from']) && empty($filters['date_to']) ? 'Tất cả'
    : (($filters['date_from'] ?? '') === ($filters['date_to'] ?? '') ? date('d/m/Y', strtotime($filters['date_from']))
    : (! empty($filters['date_from']) ? date('d/m/Y', strtotime($filters['date_from'])) : '…').' – '.(! empty($filters['date_to']) ? date('d/m/Y', strtotime($filters['date_to'])) : '…'));
  $split = function ($arr) use ($money) { return 'Tiền mặt '.$money($arr['CASH']).' · CK '.$money($arr['TRANSFER']); };
  $cats_json = array(
    'IN'  => array_map(function ($c) { return array('id' => (int) $c['id'], 'name' => $c['name']); }, $categories_in),
    'OUT' => array_map(function ($c) { return array('id' => (int) $c['id'], 'name' => $c['name']); }, $categories_out),
  );
?>
<div class="container-fluid py-3 py-md-4 cashbook-page">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-journal-bookmark"></i> Sổ quỹ</h4>
      <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo $range_text; ?> · tiền bán hàng tự động từ đơn đã thanh toán</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-success" onclick="openEntry('IN')"><i class="bi bi-plus-lg"></i> Lập phiếu thu</button>
      <button type="button" class="btn btn-danger" onclick="openEntry('OUT')"><i class="bi bi-dash-lg"></i> Lập phiếu chi</button>
      <a href="<?php echo site_url('me/cashbook/export').($qs !== '' ? '?'.htmlspecialchars($qs) : ''); ?>" class="btn btn-outline-success" data-bs-toggle="tooltip" title="Xuất Excel theo bộ lọc"><i class="bi bi-file-earmark-excel"></i><span class="d-none d-md-inline"> Excel</span></a>
      <?php if ($is_admin): ?>
        <a href="<?php echo site_url('me/cashbook/categories'); ?>" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Quản lý loại thu / chi"><i class="bi bi-tags"></i><span class="d-none d-md-inline"> Loại thu chi</span></a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php // ---------- Tổng quỹ ---------- ?>
  <div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-lg-3">
      <div class="cb-stat"><div class="cb-stat-label">Quỹ đầu kỳ</div><div class="cb-stat-value"><?php echo $money($opening['ALL']); ?></div><div class="cb-stat-sub"><?php echo $split($opening); ?></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="cb-stat cb-in"><div class="cb-stat-label"><i class="bi bi-arrow-down-circle"></i> Tổng thu</div><div class="cb-stat-value">+<?php echo $money($totals['IN']['ALL']); ?></div><div class="cb-stat-sub"><?php echo $split($totals['IN']); ?></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="cb-stat cb-out"><div class="cb-stat-label"><i class="bi bi-arrow-up-circle"></i> Tổng chi</div><div class="cb-stat-value">−<?php echo $money($totals['OUT']['ALL']); ?></div><div class="cb-stat-sub"><?php echo $split($totals['OUT']); ?></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="cb-stat cb-close"><div class="cb-stat-label">Tồn quỹ cuối kỳ</div><div class="cb-stat-value"><?php echo $money($closing['ALL']); ?></div><div class="cb-stat-sub"><?php echo $split($closing); ?></div></div>
    </div>
  </div>

  <div class="orders-card">
    <?php // ---------- Bộ lọc ---------- ?>
    <div class="orders-filters">
      <div class="orders-presets">
        <?php foreach ($ranges as $k => $label): ?>
          <a href="<?php echo $range_url($k); ?>" class="orders-chip <?php echo $range_key === $k ? 'active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
      </div>
      <?php echo form_open('me/cashbook', array('method' => 'get', 'class' => 'row g-2 align-items-end')); ?>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Từ ngày</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>"></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Đến ngày</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>"></div>
        <div class="col-6 col-md-3 col-xl-1">
          <label class="form-label">Loại</label>
          <select name="type" class="form-select form-select-sm">
            <option value="">Thu & Chi</option>
            <option value="IN" <?php echo ($filters['type'] ?? '') === 'IN' ? 'selected' : ''; ?>>Thu</option>
            <option value="OUT" <?php echo ($filters['type'] ?? '') === 'OUT' ? 'selected' : ''; ?>>Chi</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
          <label class="form-label">Hình thức</label>
          <select name="method" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            <?php foreach (payment_methods() as $code => $label): ?>
              <option value="<?php echo $code; ?>" <?php echo ($filters['method'] ?? '') === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <label class="form-label">Loại thu / chi</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            <optgroup label="Thu">
              <option value="SALE" <?php echo ($filters['category'] ?? '') === 'SALE' ? 'selected' : ''; ?>>Bán hàng (từ đơn)</option>
              <?php foreach ($categories_in as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo ($filters['category'] ?? '') === (string) $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
            </optgroup>
            <optgroup label="Chi">
              <?php foreach ($categories_out as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo ($filters['category'] ?? '') === (string) $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
            </optgroup>
          </select>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <label class="form-label">Nhân viên</label>
          <select name="handled_by" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            <?php foreach ($users as $u): ?><option value="<?php echo $u['id']; ?>" <?php echo (int) ($filters['handled_by'] ?? 0) === (int) $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['fullname']); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-4 col-xl-1"><button class="btn btn-sm btn-brand w-100"><i class="bi bi-funnel"></i> Lọc</button></div>
      <?php echo form_close(); ?>
    </div>

    <?php // ---------- Danh sách phiếu ---------- ?>
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table">
        <thead><tr><th>Mã phiếu</th><th>Thời gian</th><th>Loại thu / chi</th><th>Người nộp / nhận</th><th>Nhân viên</th><th>Hình thức</th><th class="text-end">Số tiền</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($entries as $r): $in = $r['type'] === 'IN'; ?>
          <tr>
            <td>
              <span class="fw-semibold"><?php echo htmlspecialchars($r['code']); ?></span>
              <?php if ($r['src'] === 'SALE'): ?><span class="cb-src">Đơn hàng</span><?php endif; ?>
              <?php if ($r['note']): ?><div class="small text-muted text-truncate" style="max-width:260px" title="<?php echo htmlspecialchars($r['note']); ?>"><?php echo htmlspecialchars($r['note']); ?></div><?php endif; ?>
            </td>
            <td class="small text-nowrap"><?php echo date('d/m/Y H:i', strtotime($r['dt'])); ?></td>
            <td><span class="cb-type <?php echo $in ? 'in' : 'out'; ?>"><?php echo $in ? 'Thu' : 'Chi'; ?></span> <?php echo htmlspecialchars((string) $r['category_name']); ?></td>
            <td><?php echo $r['counterparty'] !== NULL && $r['counterparty'] !== '' ? htmlspecialchars($r['counterparty']) : '<span class="text-muted">—</span>'; ?></td>
            <td><?php echo $r['handled_name'] ? htmlspecialchars($r['handled_name']) : '<span class="text-muted">—</span>'; ?></td>
            <td class="text-nowrap"><i class="bi <?php echo $pm_icons[$r['payment_method']] ?? 'bi-wallet2'; ?> text-muted"></i> <?php echo payment_method_label($r['payment_method']); ?></td>
            <td class="text-end fw-bold text-nowrap <?php echo $in ? 'text-success' : 'text-danger'; ?>"><?php echo ($in ? '+' : '−').$money($r['amount']); ?></td>
            <td class="text-end text-nowrap"><?php $this->load->view('cashbook/_row_actions', array('r' => $r, 'is_admin' => $is_admin, 'qs' => $qs)); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-md-none orders-list">
      <?php foreach ($entries as $r): $in = $r['type'] === 'IN'; ?>
        <div class="orders-item">
          <div class="orders-item-main">
            <div class="d-flex justify-content-between gap-2">
              <div class="fw-semibold"><span class="cb-type <?php echo $in ? 'in' : 'out'; ?>"><?php echo $in ? 'Thu' : 'Chi'; ?></span> <?php echo htmlspecialchars((string) $r['category_name']); ?></div>
              <div class="fw-bold text-nowrap <?php echo $in ? 'text-success' : 'text-danger'; ?>"><?php echo ($in ? '+' : '−').$money($r['amount']); ?></div>
            </div>
            <div class="small text-muted"><?php echo htmlspecialchars($r['code']); ?> · <?php echo date('d/m H:i', strtotime($r['dt'])); ?> · <?php echo payment_method_label($r['payment_method']); ?></div>
            <div class="small"><?php echo htmlspecialchars(trim(($r['counterparty'] ?? '').($r['handled_name'] ? ' · '.$r['handled_name'] : ''), ' ·')); ?></div>
            <?php if ($r['note']): ?><div class="small text-muted"><?php echo htmlspecialchars($r['note']); ?></div><?php endif; ?>
          </div>
          <div class="text-nowrap"><?php $this->load->view('cashbook/_row_actions', array('r' => $r, 'is_admin' => $is_admin, 'qs' => $qs)); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (empty($entries)): ?>
      <div class="text-center text-muted py-5"><i class="bi bi-journal fs-1 d-block mb-2 opacity-50"></i>Chưa có phiếu thu / chi nào trong khoảng này.</div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
      <nav class="orders-pager">
        <span class="small text-muted"><?php echo ($page - 1) * $per_page + 1; ?>–<?php echo ($page - 1) * $per_page + count($entries); ?> / <?php echo $total; ?> phiếu</span>
        <ul class="pagination pagination-sm mb-0 flex-wrap">
          <?php for ($p = 1; $p <= $total_pages; $p++): if ($p !== 1 && $p !== $total_pages && abs($p - $page) > 2) { if (abs($p - $page) === 3) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; continue; } ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>"><a class="page-link" href="<?php echo site_url('me/cashbook').'?'.http_build_query(array_merge($filters, array('page' => $p))); ?>"><?php echo $p; ?></a></li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>

<?php // ---------- Hộp lập / sửa phiếu thu chi ---------- ?>
<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4">
      <?php echo form_open('me/cashbook/save', array('id' => 'entryForm')); ?>
      <input type="hidden" name="id" id="entryId">
      <input type="hidden" name="type" id="entryType">
      <input type="hidden" name="back_qs" value="<?php echo htmlspecialchars($qs); ?>">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="entryTitle">Lập phiếu thu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Hình thức</label>
        <div class="pay-methods mb-3">
          <?php foreach (payment_methods() as $code => $label): ?>
            <input type="radio" class="btn-check" name="payment_method" value="<?php echo $code; ?>" id="entryPm<?php echo $code; ?>" autocomplete="off">
            <label class="pay-method" for="entryPm<?php echo $code; ?>"><i class="bi <?php echo $pm_icons[$code]; ?>"></i> <?php echo $label; ?></label>
          <?php endforeach; ?>
        </div>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="entryCategory" id="entryCategoryLabel">Loại thu</label>
            <select name="category_id" id="entryCategory" class="form-select" required></select>
          </div>
          <div class="col-12">
            <label class="form-label" for="entryAmount">Số tiền</label>
            <div class="input-group input-group-lg">
              <input type="text" name="amount" id="entryAmount" class="form-control fw-bold" inputmode="numeric" required autocomplete="off" placeholder="0">
              <span class="input-group-text">đ</span>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label" for="entryCounterparty" id="entryCounterpartyLabel">Nguồn thu / người nộp</label>
            <input type="text" name="counterparty" id="entryCounterparty" class="form-control" maxlength="150" list="counterpartyList" autocomplete="off">
            <datalist id="counterpartyList">
              <?php foreach ($counterparties as $cp): ?><option value="<?php echo htmlspecialchars($cp); ?>"><?php endforeach; ?>
            </datalist>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="entryHandledBy" id="entryHandledLabel">Nhân viên thu</label>
            <select name="handled_by" id="entryHandledBy" class="form-select">
              <?php foreach ($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['fullname']); ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="entryDate">Thời gian</label>
            <input type="datetime-local" name="entry_date" id="entryDate" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="entryNote">Ghi chú</label>
            <textarea name="note" id="entryNote" class="form-control" rows="2" maxlength="255"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
        <button class="btn btn-success px-4" id="entrySubmit"><i class="bi bi-check2"></i> Lưu phiếu thu</button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<script>
(function(){
  var CATS = <?php echo json_encode($cats_json, JSON_UNESCAPED_UNICODE); ?>;
  var ME = <?php echo (int) $current_user['id']; ?>;
  var $ = function(id){ return document.getElementById(id); };
  var fmt = function(n){ return String(n).replace(/\D/g, '').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
  var pad = function(n){ return n < 10 ? '0' + n : n; };
  function nowLocal(){ var d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); }

  // Mở hộp: openEntry('IN' | 'OUT') để lập mới, hoặc openEntry(type, entry) để sửa (ADMIN).
  window.openEntry = function(type, e){
    var isIn = type === 'IN';
    $('entryType').value = type;
    $('entryId').value = e ? e.id : '';
    $('entryTitle').textContent = e ? 'Sửa phiếu ' + e.code : (isIn ? 'Lập phiếu thu' : 'Lập phiếu chi');
    $('entryTitle').className = 'modal-title fw-bold ' + (isIn ? 'text-success' : 'text-danger');
    $('entryCategoryLabel').textContent = isIn ? 'Loại thu' : 'Loại chi';
    $('entryCounterpartyLabel').textContent = isIn ? 'Nguồn thu / người nộp' : 'Người nhận';
    $('entryCounterparty').placeholder = isIn ? 'VD: Khách lẻ, Chủ quán góp vốn…' : 'VD: Nhà cung cấp A, Điện lực…';
    $('entryHandledLabel').textContent = isIn ? 'Nhân viên thu' : 'Nhân viên chi';
    var sel = $('entryCategory');
    sel.innerHTML = '<option value="">— Chọn ' + (isIn ? 'loại thu' : 'loại chi') + ' —</option>' +
      CATS[type].map(function(c){ return '<option value="' + c.id + '">' + c.name.replace(/</g, '&lt;') + '</option>'; }).join('');
    sel.value = e ? String(e.category_id) : '';
    var pm = (e && e.payment_method) || 'CASH';
    $('entryPm' + pm).checked = true;
    $('entryAmount').value = e ? fmt(Math.round(e.amount)) : '';
    $('entryCounterparty').value = e ? (e.counterparty || '') : '';
    $('entryHandledBy').value = String(e ? (e.handled_by || ME) : ME);
    $('entryDate').value = e ? e.dt.replace(' ', 'T').slice(0, 16) : nowLocal();
    $('entryNote').value = e ? (e.note || '') : '';
    var btn = $('entrySubmit');
    btn.className = 'btn px-4 ' + (isIn ? 'btn-success' : 'btn-danger');
    btn.innerHTML = '<i class="bi bi-check2"></i> ' + (e ? 'Lưu thay đổi' : (isIn ? 'Lưu phiếu thu' : 'Lưu phiếu chi'));
    btn.disabled = false;
    bootstrap.Modal.getOrCreateInstance($('entryModal')).show();
    setTimeout(function(){ (e ? $('entryAmount') : sel).focus(); }, 350);
  };
  // Nút sửa trên từng dòng mang dữ liệu phiếu (data-entry).
  document.addEventListener('click', function(ev){
    var b = ev.target.closest && ev.target.closest('[data-entry]');
    if (b) { var e = JSON.parse(b.dataset.entry); openEntry(e.type, e); }
  });
  // Số tiền: tự thêm dấu chấm hàng nghìn khi gõ.
  $('entryAmount').addEventListener('input', function(){ this.value = fmt(this.value); });
  // Chặn bấm lưu 2 lần.
  $('entryForm').addEventListener('submit', function(){ var b = $('entrySubmit'); setTimeout(function(){ b.disabled = true; }, 0); });
})();
</script>
