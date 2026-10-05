<div class="container py-3 py-md-4" style="max-width:480px;">
  <a href="<?php echo site_url('me/kitchen'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Quay lại KDS</a>

  <div class="card kds-ticket status-<?php echo strtolower($ticket['status']); ?>">
    <div class="card-header d-flex justify-content-between">
      <span class="fw-bold"><?php echo $ticket['table_name'] ? htmlspecialchars($ticket['table_name']) : '<i class="bi bi-bag-check"></i> Mang đi'; ?></span>
      <span class="text-muted">#<?php echo htmlspecialchars($ticket['order_no']); ?></span>
    </div>
    <div class="card-body">
      <ul class="mb-3">
        <?php foreach ($ticket['items'] as $it): ?>
          <li><?php echo $it['qty']; ?>x <?php echo htmlspecialchars($it['product_name']); ?>
            <?php if ($it['note']): ?><span class="text-muted small">(<?php echo htmlspecialchars($it['note']); ?>)</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <span class="badge bg-<?php echo kitchen_status_badge($ticket['status']); ?> mb-3"><?php echo $ticket['status']; ?></span>

      <?php if ($ticket['status'] !== 'COMPLETED'): ?>
      <?php echo form_open('me/kitchen/ticket/'.$ticket['id'].'/status'); ?>
        <input type="hidden" name="redirect_to" value="<?php echo site_url('me/kitchen/ticket/'.$ticket['id']); ?>">
        <?php if ($ticket['status'] === 'NEW'): ?>
          <input type="hidden" name="status" value="PREPARING">
          <button class="btn btn-warning w-100">Bắt đầu pha chế</button>
        <?php else: ?>
          <input type="hidden" name="status" value="COMPLETED">
          <button class="btn btn-success w-100">Hoàn thành</button>
        <?php endif; ?>
      <?php echo form_close(); ?>
      <?php endif; ?>
    </div>
  </div>
</div>
