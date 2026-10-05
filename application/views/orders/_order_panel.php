<?php
  // Khối "Món đã gọi" + tổng tiền. Render lúc tải trang và render lại sau mỗi lần
  // thêm/đổi số lượng/hủy món qua AJAX (Orders::_panel_response()).
  // Biến: $order, $visible_items, $is_active, $pending_count, $kitchen_status_by_product.
?>
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between">
    <span>Món đã gọi</span>
    <?php if ($is_active && $pending_count): ?><span class="badge bg-warning text-dark"><?php echo $pending_count; ?> món chưa báo bếp</span><?php endif; ?>
  </div>
  <div class="list-group list-group-flush" id="orderedItemsList">
    <?php foreach ($visible_items as $it): ?>
      <?php $this->load->view('orders/_item_row', array('it' => $it, 'order' => $order, 'is_active' => $is_active, 'kitchen_status' => isset($kitchen_status_by_product[$it['product_id']]) ? $kitchen_status_by_product[$it['product_id']] : NULL)); ?>
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
