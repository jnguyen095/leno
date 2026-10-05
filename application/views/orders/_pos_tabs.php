<?php
  // Thanh tab POS: "Bàn" (sơ đồ bàn) | "Thực đơn" (đơn đang chọn). Ở tab Thực đơn có thêm
  // dãy chip các đơn đang phục vụ để chuyển nhanh giữa nhiều khách cùng lúc.
  // Biến: $pos_tab ('tables'|'menu'), $pos_order_id, $pos_orders (Order_model::get_active_orders()).
  $menu_url = $pos_order_id ? site_url('me/orders/'.$pos_order_id) : NULL;
?>
<ul class="nav nav-tabs pos-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?php echo $pos_tab === 'tables' ? 'active fw-semibold' : ''; ?>" href="<?php echo site_url('me/tables'); ?>">
      <i class="bi bi-grid-3x3-gap"></i> Bàn
    </a>
  </li>
  <li class="nav-item">
    <?php if ($menu_url): ?>
      <a class="nav-link <?php echo $pos_tab === 'menu' ? 'active fw-semibold' : ''; ?>" href="<?php echo $menu_url; ?>">
        <i class="bi bi-journal-text"></i> Thực đơn
      </a>
    <?php else: ?>
      <span class="nav-link disabled" title="Chọn bàn trước"><i class="bi bi-journal-text"></i> Thực đơn</span>
    <?php endif; ?>
  </li>
</ul>

<?php if ($pos_tab === 'menu' && count($pos_orders) > 1): ?>
<div class="d-flex flex-wrap gap-2 mb-3 pos-order-chips">
  <?php foreach ($pos_orders as $po): ?>
    <a href="<?php echo site_url('me/orders/'.$po['id']); ?>"
       class="btn btn-sm <?php echo (int) $po['id'] === (int) $pos_order_id ? 'btn-brand' : 'btn-outline-secondary'; ?>">
      <?php if ( ! empty($po['is_takeaway']) || empty($po['table_id'])): ?><i class="bi bi-bag-check"></i><?php endif; ?>
      <?php echo htmlspecialchars($po['table_name'] ?: 'Mang đi #'.$po['order_no']); ?>
      <span class="opacity-75 small"><?php echo money_format_vnd($po['total_amount']); ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
