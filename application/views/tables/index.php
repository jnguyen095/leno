<div class="container-fluid py-3 py-md-4">
  <?php $this->load->view('orders/_pos_tabs'); ?>

  <?php if ($paid_order_id): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center py-2">
      <span><i class="bi bi-check-circle"></i> Đã thanh toán — bàn đã về trống.</span>
      <a href="<?php echo site_url('me/orders/'.$paid_order_id.'/invoice'); ?>" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-printer"></i> In hóa đơn</a>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Sơ đồ bàn</h4>
    <div class="d-flex align-items-center gap-2">
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
          <div class="card-body text-center">
            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($t['table_name']); ?></div>
            <?php if ($t['is_takeaway']): ?>
            <div class="small text-muted mb-2"><i class="bi bi-bag-check"></i> Bán mang đi</div>
            <?php else: ?>
            <div class="small text-muted mb-2"><i class="bi bi-people"></i> <?php echo $t['capacity']; ?> chỗ</div>
            <?php endif; ?>
            <span class="badge bg-<?php echo table_status_badge($t['status']); ?> table-status-badge">
              <?php echo array('AVAILABLE'=>'Trống','OPEN'=>'Đang phục vụ','WAIT_PAYMENT'=>'Chờ TT','PAID'=>'Đã TT')[$t['status']]; ?>
            </span>
            <div class="mt-2 fw-semibold text-danger table-amount"><?php echo ! empty($t['order']) ? money_format_vnd($t['order']['total_amount']) : ''; ?></div>
          </div>
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

<script>
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
      });
    })
    .catch(function(){});
}
setInterval(refreshTables, 5000);
</script>
