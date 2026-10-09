<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Hóa đơn - <?php echo htmlspecialchars($order['order_no']); ?></title>
<link href="<?php echo asset_url('assets/css/receipt_k80.css'); ?>" rel="stylesheet">
<style>
body{ margin:0; padding:8px; background:#fff; }
.no-print{ margin-top:16px; text-align:center; }
.print-btn{ font-family:Arial,sans-serif; font-size:15px; padding:12px 24px; border:none; border-radius:8px; background:#6f4e37; color:#fff; }
@media print{ .no-print{ display:none; } body{ padding:0; } }
</style>
</head>
<body>
<div class="receipt-k80">
<?php $this->load->view('orders/_invoice_body'); ?>
</div>
<div class="no-print">
  <button class="print-btn" onclick="window.print();">🖨 In hóa đơn</button>
</div>
</body>
</html>
