<?php
  $is_active = isset($is_active) ? $is_active : in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE);
  // Phần chưa báo bếp: dương = món mới/thêm số lượng, âm = đã bớt nhưng bếp chưa biết.
  $pending_delta = ($it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0) - (int) $it['notified_qty'];
  $editable = $it['status'] === 'ACTIVE' && $is_active;
?>
<div class="list-group-item <?php echo $it['status']==='CANCELLED' ? 'opacity-50 text-decoration-line-through' : ''; ?>" data-product-id="<?php echo $it['product_id']; ?>">
  <?php // Một hàng: STT | tên (xuống dòng nếu dài) | − SL + | thành tiền | hủy. ?>
  <div class="d-flex align-items-center gap-1 gap-sm-2">
    <?php if ( ! empty($seq)): ?><span class="pos-item-seq"><?php echo (int) $seq; ?></span><?php endif; ?>
    <div class="flex-grow-1" style="min-width:0; overflow-wrap:break-word;">
      <div class="fw-semibold lh-sm"><?php echo htmlspecialchars($it['product_name']); ?></div>
      <div class="small text-muted">
        <span class="text-nowrap"><?php echo money_format_vnd($it['price']); ?><?php if ( ! $editable): ?> x <?php echo $it['qty']; ?><?php endif; ?></span>
        <?php if ($is_active && $pending_delta > 0): ?>
          <span class="badge bg-warning text-dark">Chưa báo<?php echo (int) $it['notified_qty'] > 0 ? ' +'.$pending_delta : ''; ?></span>
        <?php elseif ($is_active && $pending_delta < 0): ?>
          <span class="badge bg-danger">Chờ báo bớt <?php echo abs($pending_delta); ?></span>
        <?php endif; ?>
        <?php if ($is_active && Order_item_model::note_changed($it)): ?>
          <span class="badge bg-warning text-dark">Đổi ghi chú – chưa báo</span>
        <?php endif; ?>
      </div>
      <?php // Ghi chú món (vd "Ít đá"): bấm để sửa; món chưa có ghi chú hiện link "+ Ghi chú". ?>
      <?php if ($editable): ?>
        <button type="button" class="pos-item-note <?php echo $it['note'] ? 'has-note' : ''; ?>"
                data-id="<?php echo $it['id']; ?>" data-note="<?php echo htmlspecialchars((string) $it['note']); ?>"
                onclick="editItemNote(this)">
          <i class="bi <?php echo $it['note'] ? 'bi-chat-left-text-fill' : 'bi-plus'; ?>"></i>
          <?php echo $it['note'] ? htmlspecialchars($it['note']) : 'Ghi chú'; ?>
        </button>
      <?php elseif ($it['note']): ?>
        <div class="small text-muted"><i class="bi bi-chat-left-text"></i> <?php echo htmlspecialchars($it['note']); ?></div>
      <?php endif; ?>
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
