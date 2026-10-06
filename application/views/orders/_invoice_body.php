<?php
  // Nội dung hóa đơn K80 — dùng chung cho trang in riêng (orders/invoice.php) và in tự động
  // ngay sau khi Thanh toán (tables/index.php). Biến: $order, $items, $payment.
?>
  <div class="center bold big">Leno</div>
  <div class="center">28 Võ Văn Kiệt, BMT</div>
  <div class="center">HÓA ĐƠN BÁN HÀNG</div>
  <hr>
  <div>Số HĐ: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div><?php echo $order['table_name'] ? 'Bàn: '.htmlspecialchars($order['table_name']) : 'Mang đi'; ?></div>
  <div>Thời gian: <?php echo date('d/m/Y H:i', strtotime($order['paid_at'])); ?></div>
  <?php if ( ! empty($order['created_by_name'])): // Nhân viên tạo đơn (người mở bàn / gọi món). ?>
  <div>Nhân viên: <?php echo htmlspecialchars($order['created_by_name']); ?></div>
  <?php endif; ?>
  <hr>
  <table>
    <?php foreach ($items as $it): ?>
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
  <table>
    <tr><td>Hình thức TT</td><td class="right"><?php echo htmlspecialchars(payment_method_label($payment['payment_method'])); ?></td></tr>
  </table>
  <hr>
  <div class="center">Cảm ơn quý khách - Hẹn gặp lại!</div>
