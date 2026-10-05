<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Tạm tính - <?php echo htmlspecialchars($table['table_name']); ?></title>
<style>
body{ font-family:'Courier New',monospace; font-size:12px; color:#000; margin:0; padding:8px; }
.receipt{ width:80mm; margin:0 auto; }
hr{ border-top:1px dashed #000; }
table{ width:100%; border-collapse:collapse; }
td{ vertical-align:top; padding:2px 0; }
.center{ text-align:center; }
.right{ text-align:right; }
.bold{ font-weight:bold; }
.no-print{ margin-top:16px; }
.print-btn{ font-family:Arial,sans-serif; font-size:15px; padding:12px 24px; border:none; border-radius:8px; background:#6f4e37; color:#fff; }
@media print{ .no-print{ display:none; } }
</style>
</head>
<body>
<div class="receipt">
  <div class="center bold">Leno</div>
  <div class="center">PHIẾU TẠM TÍNH</div>
  <hr>
  <div>Bàn: <?php echo htmlspecialchars($table['table_name']); ?></div>
  <div>Mã đơn: <?php echo htmlspecialchars($order['order_no']); ?></div>
  <div>Thời gian: <?php echo date('d/m/Y H:i'); ?></div>
  <hr>
  <table>
    <?php foreach ($items as $it): ?>
    <tr>
      <td colspan="3"><?php echo htmlspecialchars($it['product_name']); ?></td>
    </tr>
    <tr>
      <td><?php echo $it['qty']; ?> x <?php echo number_format($it['price'], 0, ',', '.'); ?></td>
      <td class="right"><?php echo number_format($it['amount'], 0, ',', '.'); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <hr>
  <table>
    <tr><td>Tạm tính</td><td class="right"><?php echo number_format($order['subtotal'], 0, ',', '.'); ?></td></tr>
    <tr><td>Giảm giá</td><td class="right"><?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></td></tr>
    <tr><td>VAT</td><td class="right"><?php echo number_format($order['vat_amount'], 0, ',', '.'); ?></td></tr>
    <tr class="bold"><td>TỔNG CỘNG</td><td class="right"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td></tr>
  </table>
  <hr>
  <div class="center">-- Phiếu tạm tính, chưa phải hóa đơn --</div>
</div>
<div class="no-print center"><button class="print-btn" onclick="window.print();">🖨 In phiếu tạm tính</button></div>
</body>
</html>
