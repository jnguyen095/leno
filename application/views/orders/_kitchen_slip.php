<?php
  // Phiếu bếp K80 — dùng cho lần "Thông báo" vừa bấm và cho "In lại" trong Lịch sử báo bếp.
  // Biến: $slip (send/changed/cancel, created_at, order_note), $slip_name (data-slip), $order, $table_label.
?>
<div class="print-slip receipt-k80" data-slip="<?php echo htmlspecialchars($slip_name); ?>">
  <div class="center bold rk-title">PHIẾU BẾP</div>
  <hr>
  <div class="rk-split">
    <div class="rk-box"><span><?php echo empty($order['table_id']) ? 'Mang đi' : htmlspecialchars($table_label); ?></span></div>
    <div class="rk-lines">
      <div>Số HĐ: <?php echo htmlspecialchars($order['order_no']); ?></div>
      <div>Thời gian: <?php echo date('d/m/Y H:i', strtotime($slip['created_at'])); ?></div>
      <?php if ( ! empty($slip['order_note'])): ?><div class="italic">Ghi chú: <?php echo htmlspecialchars($slip['order_note']); ?></div><?php endif; ?>
    </div>
  </div>
  <hr>
  <?php
    $sections = array();
    if ( ! empty($slip['send']))    $sections[] = array(NULL, $slip['send'], 'send');
    if ( ! empty($slip['changed'])) $sections[] = array('ĐỔI GHI CHÚ', $slip['changed'], 'changed');
    if ( ! empty($slip['cancel']))  $sections[] = array('HỦY MÓN', $slip['cancel'], 'cancel');
  ?>
  <?php foreach ($sections as $i => $sec): ?>
    <?php if ($i > 0): ?><hr><?php endif; ?>
    <?php if ($sec[0]): ?><div class="bold"><?php echo $sec[0]; ?></div><?php endif; ?>
    <table class="rk-items">
      <colgroup><col style="width:80%"><col style="width:20%"></colgroup>
      <tr class="bold"><td>Tên món</td><td class="center">SL</td></tr>
      <?php foreach ($sec[1] as $line):
        $note = $sec[2] === 'cancel' ? NULL : ($sec[2] === 'changed' && ($line['note'] === NULL || $line['note'] === '') ? 'bỏ ghi chú' : $line['note']); ?>
      <tr>
        <td><?php echo htmlspecialchars($line['product_name']); ?><?php if ($note !== NULL && $note !== ''): ?> <i>(<?php echo htmlspecialchars($note); ?>)</i><?php endif; ?></td>
        <td class="center"><?php echo (int) $line['qty']; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
</div>
