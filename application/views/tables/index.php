<div class="container-fluid py-3 py-md-4">
  <?php // Tiêu đề "Sơ đồ bàn" nằm ngay sau 2 tab; nút "Quản lý bàn" (ADMIN) đứng trước "Toàn màn hình". ?>
  <?php ob_start(); ?>
    <span class="fw-bold fs-5 text-nowrap">Sơ đồ bàn</span>
  <?php $pos_tab_title = ob_get_clean(); ?>
  <?php ob_start(); ?>
    <?php if ($current_user['role'] === 'ADMIN'): ?>
      <a href="<?php echo site_url('me/tables/manage'); ?>" class="btn btn-sm btn-outline-secondary text-nowrap"><i class="bi bi-sliders"></i> Quản lý bàn</a>
    <?php endif; ?>
  <?php $pos_tab_actions = ob_get_clean(); ?>
  <?php $this->load->view('orders/_pos_tabs', array('pos_tab_title' => $pos_tab_title, 'pos_tab_actions' => $pos_tab_actions)); ?>

  <?php if ($paid_order_id): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center py-2">
      <span><i class="bi bi-check-circle"></i> Đã thanh toán — bàn đã về trống.</span>
      <?php if ($paid_invoice): ?>
        <button type="button" class="btn btn-sm btn-success" onclick="posPrintSlip('invoice')"><i class="bi bi-printer"></i> In lại hóa đơn</button>
      <?php endif; ?>
    </div>
  <?php endif; ?>

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
            <?php if ( ! empty($t['note'])): ?>
              <div class="small text-muted text-truncate mt-1" title="<?php echo htmlspecialchars($t['note']); ?>"><i class="bi bi-sticky"></i> <?php echo htmlspecialchars($t['note']); ?></div>
            <?php endif; ?>
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

<?php if ($paid_invoice): ?>
<!-- Hóa đơn của đơn vừa thanh toán — ẩn trên màn hình, tự mở hộp thoại in khi trang tải xong. -->
<div class="print-slip receipt-k80" data-slip="invoice">
  <?php $this->load->view('orders/_invoice_body', $paid_invoice); ?>
</div>
<script>
window.addEventListener('load', function(){ posPrintSlip('invoice'); });
</script>
<?php endif; ?>
