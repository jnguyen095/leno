<?php
  // Khối "Món đã gọi" + tổng tiền. Render lúc tải trang và render lại sau mỗi lần
  // thêm/đổi số lượng/hủy món qua AJAX (Orders::_panel_response()).
  // Biến: $order, $visible_items, $is_active, $pending_count.
?>
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between">
    <span>Món đã gọi</span>
    <?php if ($is_active && $pending_count): ?><span class="badge bg-warning text-dark"><?php echo $pending_count; ?> món chưa báo bếp</span><?php endif; ?>
  </div>
  <?php // Ghi chú cho cả đơn — in trên phiếu bếp và phiếu tạm tính. ?>
  <?php // Bên phải hàng ghi chú: "Lịch sử báo bếp" — các lần bấm "Thông báo" (giống ứng dụng POS), in lại được. ?>
  <div class="px-3 py-2 border-bottom d-flex align-items-start gap-2">
    <div class="flex-grow-1" style="min-width:0">
      <?php if ($is_active): ?>
        <button type="button" class="pos-order-note <?php echo $order['note'] ? 'has-note' : ''; ?>"
                data-note="<?php echo htmlspecialchars((string) $order['note']); ?>" onclick="editOrderNote(this)">
          <i class="bi <?php echo $order['note'] ? 'bi-journal-text' : 'bi-plus-circle'; ?>"></i>
          <?php echo $order['note'] ? '<b>Ghi chú đơn:</b> '.htmlspecialchars($order['note']) : 'Thêm ghi chú cho đơn'; ?>
        </button>
      <?php elseif ( ! empty($order['note'])): ?>
        <div class="small"><i class="bi bi-journal-text"></i> <b>Ghi chú đơn:</b> <?php echo htmlspecialchars($order['note']); ?></div>
      <?php endif; ?>
    </div>
    <button type="button" class="pos-kitchen-history-btn" onclick="openKitchenHistory()" title="Lịch sử báo bếp">
      <i class="bi bi-clock-history"></i> <span>Lịch sử báo bếp</span>
    </button>
  </div>
  <div class="list-group list-group-flush" id="orderedItemsList">
    <?php foreach (array_values($visible_items) as $i => $it): ?>
      <?php $this->load->view('orders/_item_row', array('it' => $it, 'order' => $order, 'is_active' => $is_active, 'seq' => $i + 1)); ?>
    <?php endforeach; ?>
    <?php if (empty($visible_items)): ?>
      <div class="list-group-item text-muted text-center py-4">Chưa có món nào — bấm vào món ở thực đơn để thêm.</div>
    <?php endif; ?>
  </div>
  <div class="card-footer bg-white">
    <div class="d-flex justify-content-between small"><span>Tạm tính</span><span><?php echo money_format_vnd($order['subtotal']); ?></span></div>
    <div class="d-flex justify-content-between small"><span>Giảm giá</span><span>-<?php echo money_format_vnd($order['discount_amount']); ?></span></div>
    <div class="d-flex justify-content-between small"><span>VAT</span><span><?php echo money_format_vnd($order['vat_amount']); ?></span></div>
    <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>Tổng cộng</span><span class="text-brand"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
  </div>
</div>
