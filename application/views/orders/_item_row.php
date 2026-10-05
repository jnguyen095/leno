<?php
  $is_active = isset($is_active) ? $is_active : in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE);
  // Phần chưa báo bếp: dương = món mới/thêm số lượng, âm = đã bớt nhưng bếp chưa biết.
  $pending_delta = ($it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0) - (int) $it['notified_qty'];
  $img_classes = 'rounded border flex-shrink-0';
  $editable = $it['status'] === 'ACTIVE' && $is_active;
?>
<div class="list-group-item <?php echo $it['status']==='CANCELLED' ? 'opacity-50 text-decoration-line-through' : ''; ?>" data-product-id="<?php echo $it['product_id']; ?>">
  <?php // Một hàng: [ảnh] tên (xuống dòng nếu dài) | − SL + | thành tiền | hủy. Màn hình hẹp ẩn ảnh để tên đủ chỗ. ?>
  <div class="d-flex align-items-center gap-1 gap-sm-2">
    <?php if ($it['image']): ?>
      <img src="<?php echo base_url('assets/'.$it['image']); ?>" style="width:44px;height:44px;object-fit:cover;" class="d-none d-sm-block <?php echo $img_classes; ?>">
    <?php else: ?>
      <div class="d-none d-sm-flex align-items-center justify-content-center bg-light text-muted flex-shrink-0 <?php echo $img_classes; ?>" style="width:44px;height:44px;"><i class="bi bi-cup-straw"></i></div>
    <?php endif; ?>
    <div class="flex-grow-1" style="min-width:0; overflow-wrap:break-word;">
      <div class="fw-semibold lh-sm"><?php echo htmlspecialchars($it['product_name']); ?></div>
      <div class="small text-muted">
        <span class="text-nowrap"><?php echo money_format_vnd($it['price']); ?><?php if ( ! $editable): ?> x <?php echo $it['qty']; ?><?php endif; ?></span>
        <?php if ($it['note']): ?> — <?php echo htmlspecialchars($it['note']); ?><?php endif; ?>
        <?php if ($is_active && $pending_delta > 0): ?>
          <span class="badge bg-warning text-dark">Chưa báo<?php echo (int) $it['notified_qty'] > 0 ? ' +'.$pending_delta : ''; ?></span>
        <?php elseif ($is_active && $pending_delta < 0): ?>
          <span class="badge bg-danger">Chờ báo bớt <?php echo abs($pending_delta); ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($editable): ?>
    <div class="qty-stepper">
      <?php // Không cho giảm về 0 — muốn bỏ món thì bấm nút thùng rác (có hỏi xác nhận). ?>
      <button type="button" onclick="changeItemQty(<?php echo $it['id']; ?>, <?php echo (int) $it['qty'] - 1; ?>)" title="Bớt 1" <?php echo (int) $it['qty'] <= 1 ? 'disabled' : ''; ?>><i class="bi bi-dash-lg"></i></button>
      <span><?php echo (int) $it['qty']; ?></span>
      <button type="button" onclick="changeItemQty(<?php echo $it['id']; ?>, <?php echo (int) $it['qty'] + 1; ?>)" title="Thêm 1"><i class="bi bi-plus-lg"></i></button>
    </div>
    <?php endif; ?>
    <div class="fw-semibold text-nowrap text-end flex-shrink-0 pos-item-amount"><?php echo money_format_vnd($it['amount']); ?></div>
    <?php if ($editable): ?>
    <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removeItem(<?php echo $it['id']; ?>)" title="Hủy món"><i class="bi bi-trash"></i></button>
    <?php endif; ?>
  </div>
</div>
