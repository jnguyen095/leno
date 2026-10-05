<div class="container-fluid py-3 py-md-4">
  <h4 class="fw-bold mb-3"><i class="bi bi-cash-coin"></i> Thu ngân</h4>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold">Bàn đang phục vụ — chờ đóng bill</div>
        <div class="list-group list-group-flush">
          <?php foreach ($open_orders as $o): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?php echo $o['table_name'] ? htmlspecialchars($o['table_name']) : '<i class="bi bi-bag-check"></i> Mang đi'; ?></div>
              <div class="small text-muted">#<?php echo htmlspecialchars($o['order_no']); ?> — <?php echo money_format_vnd($o['total_amount']); ?></div>
            </div>
            <?php echo form_open('me/cashier/'.$o['id'].'/close-bill'); ?>
              <button class="btn btn-sm btn-warning">Đóng bill</button>
            <?php echo form_close(); ?>
          </div>
          <?php endforeach; ?>
          <?php if (empty($open_orders)): ?>
            <div class="list-group-item text-muted text-center py-3">Không có bàn nào.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-semibold">Chờ thanh toán</div>
        <div class="list-group list-group-flush">
          <?php foreach ($wait_orders as $o): ?>
          <a href="<?php echo site_url('me/cashier/'.$o['id']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?php echo $o['table_name'] ? htmlspecialchars($o['table_name']) : '<i class="bi bi-bag-check"></i> Mang đi'; ?></div>
              <div class="small text-muted">#<?php echo htmlspecialchars($o['order_no']); ?></div>
            </div>
            <div class="fw-bold text-brand"><?php echo money_format_vnd($o['total_amount']); ?></div>
          </a>
          <?php endforeach; ?>
          <?php if (empty($wait_orders)): ?>
            <div class="list-group-item text-muted text-center py-3">Không có đơn nào.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>setInterval(function(){location.reload();}, 15000);</script>
