<?php
  // Viền ảnh món tô theo trạng thái pha chế (ưu tiên NEW > PREPARING > COMPLETED) —
  // chớp khi còn NEW/PREPARING, viền đặc khi COMPLETED. Món hủy hoặc chưa lên bếp thì không tô.
  $kitchen_status = isset($kitchen_status) ? $kitchen_status : NULL;
  $img_classes = 'rounded border flex-shrink-0 item-kitchen-img';
  if ($kitchen_status && $it['status'] !== 'CANCELLED')
  {
      $img_classes .= ' border-'.kitchen_status_badge($kitchen_status);
      if ($kitchen_status !== 'COMPLETED')
      {
          $img_classes .= ' order-kitchen-flash';
      }
  }
?>
<div class="list-group-item d-flex justify-content-between align-items-center <?php echo $it['status']==='CANCELLED' ? 'opacity-50 text-decoration-line-through' : ''; ?>" data-product-id="<?php echo $it['product_id']; ?>">
  <div class="d-flex align-items-center gap-2">
    <?php if ($it['image']): ?>
      <img src="<?php echo base_url('assets/'.$it['image']); ?>" style="width:44px;height:44px;object-fit:cover;" class="<?php echo $img_classes; ?>">
    <?php else: ?>
      <div class="d-flex align-items-center justify-content-center bg-light text-muted flex-shrink-0 <?php echo $img_classes; ?>" style="width:44px;height:44px;"><i class="bi bi-cup-straw"></i></div>
    <?php endif; ?>
    <div>
      <div class="fw-semibold"><?php echo htmlspecialchars($it['product_name']); ?></div>
      <div class="small text-muted"><?php echo money_format_vnd($it['price']); ?> x <?php echo $it['qty']; ?><?php if ($it['note']): ?> — <?php echo htmlspecialchars($it['note']); ?><?php endif; ?></div>
    </div>
  </div>
  <div class="d-flex align-items-center gap-2">
    <div class="fw-semibold"><?php echo money_format_vnd($it['amount']); ?></div>
    <?php if ($it['status'] === 'ACTIVE' && $order['status'] === 'OPEN'): ?>
    <div class="dropdown">
      <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <?php echo form_open('me/orders/'.$order['id'].'/update-item/'.$it['id'], array('class' => 'px-3 py-1 d-flex gap-1')); ?>
            <input type="number" name="qty" min="1" value="<?php echo $it['qty']; ?>" class="form-control form-control-sm" style="width:70px;">
            <button class="btn btn-sm btn-outline-primary">Sửa</button>
          <?php echo form_close(); ?>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <?php echo form_open('me/orders/'.$order['id'].'/cancel-item/'.$it['id'], array('onsubmit' => "return confirm('Hủy món này?');")); ?>
            <button class="dropdown-item text-danger">Hủy món</button>
          <?php echo form_close(); ?>
        </li>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>
