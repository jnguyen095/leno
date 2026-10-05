<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Danh sách sản phẩm kho</title>
<style>
body{ font-family: Arial, sans-serif; font-size: 13px; color: #000; margin: 0; padding: 16px; }
h2{ margin: 0 0 4px 0; }
.meta{ color: #444; margin-bottom: 12px; font-size: 12px; }
table{ width: 100%; border-collapse: collapse; }
th, td{ border: 1px solid #999; padding: 5px 8px; text-align: left; }
th{ background: #f0f0f0; }
.text-end{ text-align: right; }
.no-print{ margin-top: 16px; text-align: center; }
.print-btn{ font-family: Arial, sans-serif; font-size: 15px; padding: 10px 20px; border: none; border-radius: 8px; background: #6f4e37; color: #fff; margin: 0 4px; cursor: pointer; }
@media print{ .no-print{ display: none; } }
</style>
</head>
<body onload="window.print();">

<h2>Danh sách sản phẩm kho</h2>
<div class="meta">
  Ngày in: <?php echo date('d/m/Y H:i'); ?>
  <?php if ($category_name): ?> — Danh mục: <?php echo htmlspecialchars($category_name); ?><?php endif; ?>
  <?php if ($stock_status === 'LOW'): ?> — Chỉ sắp hết hàng<?php elseif ($stock_status === 'OK'): ?> — Chỉ đủ hàng<?php endif; ?>
  <?php if ($keyword): ?> — Từ khóa: "<?php echo htmlspecialchars($keyword); ?>"<?php endif; ?>
  — Tổng <?php echo count($items); ?> sản phẩm
</div>

<table>
  <thead>
    <tr><th>STT</th><th>Tên</th><th>Danh mục</th><th>ĐVT</th><th class="text-end">Tồn kho</th></tr>
  </thead>
  <tbody>
  <?php $stt = 1; foreach ($items as $it): ?>
    <tr>
      <td><?php echo $stt++; ?></td>
      <td><?php echo htmlspecialchars($it['name']); ?></td>
      <td><?php echo htmlspecialchars($it['category_name']); ?></td>
      <td><?php echo htmlspecialchars($it['unit_name']); ?></td>
      <td class="text-end"><?php echo rtrim(rtrim(number_format($it['qty_on_hand'], 2, '.', ''), '0'), '.'); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($items)): ?>
    <tr><td colspan="5" style="text-align:center;">Không có sản phẩm nào.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<div class="no-print">
  <button class="print-btn" onclick="window.print();">In lại</button>
  <button class="print-btn" onclick="window.close();">Đóng</button>
</div>
</body>
</html>
