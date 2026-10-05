<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR - <?php echo htmlspecialchars($table['table_name']); ?></title>
<style>
body{ font-family: Arial, sans-serif; text-align:center; padding:24px; }
.label{ width:300px; margin:0 auto; border:2px dashed #6f4e37; border-radius:12px; padding:20px; }
img{ width:220px; height:220px; }
.no-print{ margin-top:16px; }
.print-btn{ font-size:15px; padding:12px 24px; border:none; border-radius:8px; background:#6f4e37; color:#fff; }
@media print{ .no-print{ display:none; } }
</style>
</head>
<body>
  <div class="label">
    <h3><?php echo htmlspecialchars($table['table_name']); ?></h3>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode($menu_url); ?>" alt="QR">
    <p>Quét mã để xem thực đơn &amp; đặt món</p>
<!--    <small>--><?php //echo htmlspecialchars($menu_url); ?><!--</small>-->
  </div>
  <div class="no-print"><button class="print-btn" onclick="window.print();">🖨 In mã QR</button></div>
</body>
</html>
