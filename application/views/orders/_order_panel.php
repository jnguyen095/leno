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
  <?php
    // Bảng giá + khuyến mãi của đơn: chọn ở Sơ đồ bàn (áp cho mọi đơn đang mở). Ở đây người bán chỉ xem,
    // hoặc bấm ✕ để bỏ cho riêng đơn này (về giá gốc / không giảm).
    $has_book = ! empty($order['price_book_id']);
    $has_disc = $order['discount_type'] !== NULL;
    // Khuyến mãi đã áp nhưng đơn chưa đủ giá trị tối thiểu.
    $disc_short_of = $has_disc && (float) $order['discount_amount'] <= 0 ? max(0, (float) $order['discount_min_order'] - (float) $order['subtotal']) : 0;
  ?>
  <?php if ($has_book || $has_disc): ?>
    <div class="px-3 py-2 border-bottom pos-offers">
      <div class="pos-offer-chip <?php echo $has_book ? 'is-on' : 'is-none'; ?>">
        <i class="bi bi-tags"></i>
        <div class="pos-offer-text">
          <small>Bảng giá</small>
          <span><?php echo $has_book ? htmlspecialchars($order['price_book_name']) : 'Giá gốc'; ?></span>
        </div>
        <?php if ($has_book && $is_active): ?>
          <button type="button" class="pos-offer-remove" onclick="removeOffer('price-book')" title="Bỏ bảng giá — đơn này về giá gốc"><i class="bi bi-x-lg"></i></button>
        <?php endif; ?>
      </div>
      <div class="pos-offer-chip <?php echo $has_disc ? ($disc_short_of > 0 ? 'is-wait' : 'is-disc') : 'is-none'; ?>">
        <i class="bi bi-percent"></i>
        <div class="pos-offer-text">
          <small>Khuyến mãi</small>
          <span><?php echo $has_disc ? htmlspecialchars($order['discount_name']) : 'Không áp dụng'; ?></span>
          <?php if ($has_disc): ?>
            <em><?php echo $disc_short_of > 0 ? 'Mua thêm '.money_format_vnd($disc_short_of).' để được giảm' : 'Đã giảm '.money_format_vnd($order['discount_amount']); ?></em>
          <?php endif; ?>
        </div>
        <?php if ($has_disc && $is_active): ?>
          <button type="button" class="pos-offer-remove" onclick="removeOffer('discount')" title="Bỏ khuyến mãi cho đơn này"><i class="bi bi-x-lg"></i></button>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
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
    <div class="d-flex justify-content-between small <?php echo (float) $order['discount_amount'] > 0 ? 'text-success fw-semibold' : ''; ?>"><span>Khuyến mãi<?php echo ! empty($order['discount_name']) ? ' ('.htmlspecialchars($order['discount_name']).')' : ''; ?></span><span>-<?php echo money_format_vnd($order['discount_amount']); ?></span></div>
    <div class="d-flex justify-content-between small"><span>VAT</span><span><?php echo money_format_vnd($order['vat_amount']); ?></span></div>
    <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>Tổng cộng</span><span class="text-brand"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
  </div>
</div>
