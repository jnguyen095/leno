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
    // Hàng "Bảng giá | Khuyến mãi": người bán tự chọn (không tự áp) trong các bảng giá / khuyến mãi có hiệu lực hôm nay.
    // Bảng giá đổi -> tính lại mọi món; khuyến mãi -> giảm trên tạm tính (chưa đủ đơn tối thiểu thì giảm 0đ).
    // Lựa chọn đơn đang dùng mà hôm nay hết hiệu lực vẫn hiện để giữ nguyên.
    $price_books = isset($price_books) ? $price_books : array();
    $discounts = isset($discounts) ? $discounts : array();
    $book_listed = ! $order['price_book_id'] || in_array((int) $order['price_book_id'], array_map('intval', array_column($price_books, 'id')), TRUE);
    $disc_listed = ! $order['discount_id'] || in_array((int) $order['discount_id'], array_map('intval', array_column($discounts, 'id')), TRUE);
    $has_disc = $order['discount_type'] !== NULL;
    $disc_short = function ($d) {
      $min = (float) $d['min_order'] > 0 ? ' · từ '.number_format((float) $d['min_order'] / 1000, 0, ',', '.').'K' : '';
      return $d['name'].$min;
    };
    // Khuyến mãi đã chọn nhưng đơn chưa đủ giá trị tối thiểu.
    $disc_short_of = $has_disc && (float) $order['discount_amount'] <= 0 ? max(0, (float) $order['discount_min_order'] - (float) $order['subtotal']) : 0;
  ?>
  <?php if ($is_active && ($price_books || $discounts || $order['price_book_id'] || $has_disc)): ?>
    <div class="px-3 py-2 border-bottom pos-offers">
      <div class="pos-offer pos-price-book <?php echo $order['price_book_id'] ? 'is-on' : ''; ?>">
        <label for="priceBookSelect"><i class="bi bi-tags"></i> Bảng giá</label>
        <select id="priceBookSelect" class="form-select form-select-sm" onchange="setPriceBook(this)" <?php echo $price_books || $order['price_book_id'] ? '' : 'disabled'; ?>>
          <option value=""><?php echo $price_books || $order['price_book_id'] ? 'Giá gốc' : 'Không có bảng giá hôm nay'; ?></option>
          <?php if ( ! $book_listed): ?>
            <option value="<?php echo (int) $order['price_book_id']; ?>" selected><?php echo htmlspecialchars($order['price_book_name']); ?> (hết hiệu lực)</option>
          <?php endif; ?>
          <?php foreach ($price_books as $pb): ?>
            <option value="<?php echo (int) $pb['id']; ?>" <?php echo (int) $order['price_book_id'] === (int) $pb['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pb['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="pos-offer pos-discount <?php echo $has_disc ? ($disc_short_of > 0 ? 'is-wait' : 'is-on') : ''; ?>">
        <label for="discountSelect"><i class="bi bi-percent"></i> Khuyến mãi</label>
        <select id="discountSelect" class="form-select form-select-sm" onchange="setDiscount(this)" <?php echo $discounts || $has_disc ? '' : 'disabled'; ?>>
          <option value=""><?php echo $discounts || $has_disc ? 'Không áp dụng' : 'Không có khuyến mãi hôm nay'; ?></option>
          <?php if ($has_disc && ! $disc_listed): ?>
            <option value="<?php echo (int) $order['discount_id']; ?>" selected><?php echo htmlspecialchars($order['discount_name']); ?> (hết hiệu lực)</option>
          <?php endif; ?>
          <?php foreach ($discounts as $dc): ?>
            <option value="<?php echo (int) $dc['id']; ?>" <?php echo (int) $order['discount_id'] === (int) $dc['id'] ? 'selected' : ''; ?>
                    title="<?php echo htmlspecialchars(Discount_model::describe($dc['discount_type'], $dc['value'], $dc['max_discount'], $dc['min_order'])); ?>"><?php echo htmlspecialchars($disc_short($dc)); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($has_disc): ?>
          <div class="pos-offer-hint">
            <?php if ($disc_short_of > 0): ?>
              <i class="bi bi-hourglass-split"></i> Mua thêm <?php echo money_format_vnd($disc_short_of); ?> để được giảm
            <?php else: ?>
              <i class="bi bi-check-circle-fill"></i> Đã giảm <?php echo money_format_vnd($order['discount_amount']); ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php elseif ( ! $is_active && ( ! empty($order['price_book_name']) || ! empty($order['discount_name']))): ?>
    <div class="px-3 py-2 border-bottom small d-flex flex-wrap gap-3">
      <?php if ( ! empty($order['price_book_name'])): ?><span><i class="bi bi-tags"></i> <b>Bảng giá:</b> <?php echo htmlspecialchars($order['price_book_name']); ?></span><?php endif; ?>
      <?php if ( ! empty($order['discount_name'])): ?><span><i class="bi bi-percent"></i> <b>Khuyến mãi:</b> <?php echo htmlspecialchars($order['discount_name']); ?> (−<?php echo money_format_vnd($order['discount_amount']); ?>)</span><?php endif; ?>
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
