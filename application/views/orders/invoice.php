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
<?php $this->load->view('orders/_invoice_body'); ?>
</div>
<div class="no-print center">
  <button class="print-btn" onclick="window.print();">🖨 In hóa đơn</button>
</div>
</body>
</html>
