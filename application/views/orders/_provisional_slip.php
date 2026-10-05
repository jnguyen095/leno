<?php
  // Phiếu tạm tính K80 (in ngay trên trang). Render lại sau mỗi thay đổi món qua AJAX để luôn khớp đơn.
  // Biến: $order, $table_label, $active_items.
?>
<div class="print-slip receipt-k80" data-slip="provisional">
  <div class="center bold big">Leno</div>
  <div class="center">PHIẾU TẠM TÍNH</div>
  <hr>
  <div><?php echo empty($order['table_id']) ? 'Mang đi' : 'Bàn: '.htmlspecialchars($table_label); ?></div>
  <div>Mã đơn: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div>Thời gian: <span class="js-print-time"></span></div>
  <hr>
  <table>
    <?php foreach ($active_items as $it): ?>
    <tr><td colspan="2"><?php echo htmlspecialchars($it['product_name']); ?></td></tr>
    <tr>
      <td><?php echo $it['qty']; ?> x <?php echo number_format($it['price'], 0, ',', '.'); ?></td>
      <td class="right"><?php echo number_format($it['amount'], 0, ',', '.'); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <hr>
  <table>
    <tr><td>Tạm tính</td><td class="right"><?php echo number_format($order['subtotal'], 0, ',', '.'); ?></td></tr>
    <tr><td>Giảm giá</td><td class="right">-<?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></td></tr>
    <tr><td>VAT</td><td class="right"><?php echo number_format($order['vat_amount'], 0, ',', '.'); ?></td></tr>
    <tr class="bold big"><td>TỔNG CỘNG</td><td class="right"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td></tr>
  </table>
  <hr>
  <div class="center">-- Phiếu tạm tính, chưa phải hóa đơn --</div>
</div>
