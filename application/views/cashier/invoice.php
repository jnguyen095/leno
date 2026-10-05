<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Hóa đơn - <?php echo htmlspecialchars($order['order_no']); ?></title>
<style>
body{ font-family:'Courier New',monospace; font-size:12px; color:#000; margin:0; padding:8px; }
.receipt{ width:80mm; margin:0 auto; }
hr{ border-top:1px dashed #000; }
table{ width:100%; border-collapse:collapse; }
td{ vertical-align:top; padding:2px 0; }
.center{ text-align:center; }
.right{ text-align:right; }
.bold{ font-weight:bold; }
.big{ font-size:14px; }
.no-print{ margin-top:16px; }
.print-btn{ font-family:Arial,sans-serif; font-size:15px; padding:12px 24px; border:none; border-radius:8px; background:#6f4e37; color:#fff; }
@media print{ .no-print{ display:none; } }
</style>
</head>
<body>
<div class="receipt">
  <div class="center bold big">Leno</div>
  <div class="center">28 Võ Văn Kiệt, BMT</div>
  <div class="center">HÓA ĐƠN BÁN HÀNG</div>
  <hr>
  <div>Số HĐ: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div><?php echo $order['table_name'] ? 'Bàn: '.htmlspecialchars($order['table_name']) : 'Mang đi'; ?></div>
  <div>Thời gian: <?php echo date('d/m/Y H:i', strtotime($order['paid_at'])); ?></div>
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
    <tr><td>Hình thức TT</td><td class="right"><?php echo $payment['payment_method']; ?></td></tr>
    <tr><td>Khách đưa</td><td class="right"><?php echo number_format($payment['received_amount'], 0, ',', '.'); ?></td></tr>
    <tr><td>Tiền thối</td><td class="right"><?php echo number_format($payment['change_amount'], 0, ',', '.'); ?></td></tr>
  </table>
  <hr>
  <div class="center">Cảm ơn quý khách - Hẹn gặp lại!</div>
</div>
<div class="no-print center">
  <button class="print-btn" onclick="window.print();">🖨 In hóa đơn</button>
</div>
</body>
</html>
