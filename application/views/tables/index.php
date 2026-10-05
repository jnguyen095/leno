<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Sơ đồ bàn</h4>
    <div class="d-flex align-items-center gap-2">
      <div class="small">
        <span class="badge bg-success">Trống</span>
        <span class="badge bg-primary">Đang phục vụ</span>
        <span class="badge bg-warning text-dark">Chờ TT</span>
      </div>
      <?php if ($current_user['role'] === 'ADMIN'): ?>
      <a href="<?php echo site_url('me/tables/manage'); ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-sliders"></i> Quản lý bàn</a>
      <?php endif; ?>
    </div>
  </div>

  <?php
    $render_table_card = function ($t) {
  ?>
    <div class="col-6 col-sm-4 col-md-3 col-lg-2" data-table-id="<?php echo $t['id']; ?>">
      <?php if ($t['status'] === 'AVAILABLE'): ?>
        <a href="<?php echo site_url('me/tables/'.$t['id'].'/open'); ?>" class="text-decoration-none table-link">
      <?php else: ?>
        <a href="<?php echo site_url('me/tables/'.$t['id']); ?>" class="text-decoration-none table-link">
      <?php endif; ?>
        <div class="card table-card border-0 h-100 position-relative">
          <?php if ( ! empty($t['pending_calls'])): ?>
            <span class="call-alert-badge call-alert-icon" title="<?php echo in_array('PAYMENT', $t['pending_calls'], TRUE) ? 'Yêu cầu thanh toán' : 'Cần hỗ trợ'; ?>">
              <i class="bi <?php echo in_array('PAYMENT', $t['pending_calls'], TRUE) ? 'bi-credit-card' : 'bi-bell-fill'; ?>"></i>
            </span>
          <?php else: ?>
            <span class="call-alert-badge call-alert-icon d-none"><i class="bi bi-bell-fill"></i></span>
          <?php endif; ?>
          <div class="card-body text-center">
            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($t['table_name']); ?></div>
            <div class="small text-muted mb-2"><i class="bi bi-people"></i> <?php echo $t['capacity']; ?> chỗ</div>
            <span class="badge bg-<?php echo table_status_badge($t['status']); ?> table-status-badge">
              <?php echo array('AVAILABLE'=>'Trống','OPEN'=>'Đang phục vụ','WAIT_PAYMENT'=>'Chờ TT','PAID'=>'Đã TT')[$t['status']]; ?>
            </span>
            <div class="mt-2 fw-semibold text-danger table-amount"><?php echo ! empty($t['order']) ? money_format_vnd($t['order']['total_amount']) : ''; ?></div>
          </div>
            <button type="button" class="btn btn-sm w-100 mt-1"
                    onclick="event.stopPropagation(); event.preventDefault(); showQrModal(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['table_name'], ENT_QUOTES); ?>', '<?php echo $t['qr_token']; ?>');">
                <i class="bi bi-qr-code"></i> QR
            </button>
        </div>
      </a>
    </div>
  <?php
    };
  ?>

  <div class="row g-3 mb-4" id="tablesGrid">
    <?php foreach ($tables as $t) $render_table_card($t); ?>
  </div>
</div>

<!-- QR popup (thay vì mở trang mới) -->
<div class="modal fade" id="qrModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content text-center">
      <div class="modal-header">
        <h5 class="modal-title" id="qrModalTitle">QR bàn</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <img id="qrModalImage" src="" alt="QR" style="width:220px;height:220px;">
        <p class="small text-muted mt-2 mb-1">Quét mã để xem thực đơn &amp; đặt món</p>
        <div class="small text-break" id="qrModalUrl"></div>
      </div>
      <div class="modal-footer justify-content-center">
        <a id="qrModalPrintLink" href="#" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Mở trang in</a>
      </div>
    </div>
  </div>
</div>

<script>
function showQrModal(tableId, tableName, qrToken){
  var menuUrl = '<?php echo base_url('menu'); ?>/' + qrToken;
  document.getElementById('qrModalTitle').textContent = tableName;
  document.getElementById('qrModalImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(menuUrl);
  document.getElementById('qrModalUrl').textContent = menuUrl;
  document.getElementById('qrModalPrintLink').href = '<?php echo base_url('me/tables'); ?>/' + tableId + '/qr.html';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('qrModal')).show();
}

var STATUS_LABEL = {AVAILABLE:'Trống', OPEN:'Đang phục vụ', WAIT_PAYMENT:'Chờ TT', PAID:'Đã TT'};
var STATUS_COLOR = {AVAILABLE:'success', OPEN:'primary', WAIT_PAYMENT:'warning', PAID:'info'};

function refreshTables(){
  fetch('<?php echo base_url('api/tables/status'); ?>')
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res.success) return;
      res.tables.forEach(function(t){
        var col = document.querySelector('[data-table-id="'+t.id+'"]');
        if (!col) return;
        var badge = col.querySelector('.table-status-badge');
        badge.className = 'badge bg-'+STATUS_COLOR[t.status]+' table-status-badge';
        badge.textContent = STATUS_LABEL[t.status];
        col.querySelector('.table-amount').textContent = t.total_amount ? Number(t.total_amount).toLocaleString('vi-VN')+'đ' : '';
        var link = col.querySelector('.table-link');
        link.setAttribute('href', t.status === 'AVAILABLE' ? '<?php echo base_url('me/tables'); ?>/'+t.id+'/open.html' : '<?php echo base_url('me/tables'); ?>/'+t.id+'.html');

        var callIcon = col.querySelector('.call-alert-icon');
        var hasCall = t.pending_calls && t.pending_calls.length > 0;
        callIcon.classList.toggle('d-none', !hasCall);
        if (hasCall){
          var isPayment = t.pending_calls.indexOf('PAYMENT') !== -1;
          callIcon.title = isPayment ? 'Yêu cầu thanh toán' : 'Cần hỗ trợ';
          callIcon.innerHTML = '<i class="bi ' + (isPayment ? 'bi-credit-card' : 'bi-bell-fill') + '"></i>';
        }
      });
    })
    .catch(function(){});
}
setInterval(refreshTables, 5000);
</script>
