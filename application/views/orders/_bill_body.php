<?php
  // Phiếu tạm tính (đơn đang mở) / phiếu tính tiền (đã thanh toán) K80 — cùng bố cục với ứng dụng
  // POS Flutter (lib/printing/tickets.dart: Tickets.bill). Biến: $order, $items (món còn hiệu lực),
  // $payment (NULL khi chưa thanh toán), $bank_qr (Setting_model::get_bank_qr(), tùy chọn).
  $paid = $order['status'] === 'PAID';
  $money = function ($v) { return number_format(round((float) $v), 0, ',', '.'); };
  $show_qr = ! $paid && ! empty($bank_qr['enabled']) && (float) $order['total_amount'] > 0;
  // Tên quán / địa chỉ / SĐT / lời cảm ơn: Cài đặt → Thông tin in phiếu.
  $CI =& get_instance();
  $CI->load->model('Setting_model');
  $receipt = $CI->Setting_model->get_receipt_info();
  $contact = implode(' - ', array_filter(array($receipt['address'], $receipt['phone']), 'strlen'));
?>
  <div class="center bold rk-shop"><?php echo htmlspecialchars($receipt['shop_name']); ?></div>
  <?php if ($contact !== ''): ?><div class="center"><?php echo htmlspecialchars($contact); ?></div><?php endif; ?>
  <div class="center bold rk-title"><?php echo $paid ? 'PHIẾU TÍNH TIỀN' : 'PHIẾU TẠM TÍNH'; ?></div>
  <?php if ( ! empty($order['created_by_name'])): ?>
  <div class="center">NVBH: <?php echo htmlspecialchars($order['created_by_name']); ?></div>
  <?php endif; ?>
  <hr>
  <div class="rk-split">
    <div class="rk-box"><span><?php echo empty($order['table_id']) ? 'Mang đi' : htmlspecialchars($order['table_name']); ?></span></div>
    <div class="rk-lines">
      <div>Số HĐ: <?php echo htmlspecialchars($order['order_no']); ?></div>
      <div>Thời gian: <?php if ($paid): ?><?php echo date('d/m/Y H:i', strtotime($order['paid_at'])); ?><?php else: ?><span class="js-print-time"><?php echo date('d/m/Y H:i'); ?></span><?php endif; ?></div>
      <?php if ( ! $paid && ! empty($order['note'])): ?><div class="italic">Ghi chú: <?php echo htmlspecialchars($order['note']); ?></div><?php endif; ?>
    </div>
  </div>
  <hr>
  <table class="rk-items">
    <colgroup><col style="width:46%"><col style="width:21%"><col style="width:9%"><col style="width:24%"></colgroup>
    <tr class="bold"><td>Tên món</td><td class="right">Đ.Giá</td><td class="center">SL</td><td class="right">Tiền</td></tr>
    <?php foreach ($items as $it): ?>
    <tr>
      <td><?php echo htmlspecialchars($it['product_name']); ?><?php if ( ! $paid && ! empty($it['note'])): ?> <i>(<?php echo htmlspecialchars($it['note']); ?>)</i><?php endif; ?></td>
      <td class="right"><?php echo $money($it['price']); ?></td>
      <td class="center"><?php echo (int) $it['qty']; ?></td>
      <td class="right"><?php echo $money($it['amount']); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <hr>
  <table>
    <tr><td>Tạm tính</td><td class="right"><?php echo $money($order['subtotal']); ?></td></tr>
    <tr><td>Chiết khấu</td><td class="right"><?php echo (float) $order['discount_amount'] > 0 ? '-'.$money($order['discount_amount']) : '0'; ?></td></tr>
    <?php if ((float) $order['vat_amount'] > 0): ?>
    <tr><td>VAT</td><td class="right"><?php echo $money($order['vat_amount']); ?></td></tr>
    <?php endif; ?>
    <tr class="bold"><td>TỔNG CỘNG</td><td class="right"><?php echo $money($order['total_amount']); ?></td></tr>
  </table>
  <?php if ($show_qr): ?>
  <div class="rk-qr js-vietqr" data-qr="<?php echo htmlspecialchars(vietqr_payload($bank_qr['bin'], $bank_qr['account_no'], (int) round($order['total_amount']), $order['order_no'])); ?>"></div>
  <div class="center bold">Quét mã để chuyển khoản</div>
  <div class="center"><?php echo htmlspecialchars($bank_qr['bank_name'].' - '.$bank_qr['account_no']); ?></div>
  <?php if ($bank_qr['account_name'] !== ''): ?><div class="center"><?php echo htmlspecialchars($bank_qr['account_name']); ?></div><?php endif; ?>
  <?php endif; ?>
  <?php if ($paid && ! empty($payment)): ?>
  <table><tr><td>Hình thức TT</td><td class="right"><?php echo htmlspecialchars(payment_method_label($payment['payment_method'])); ?></td></tr></table>
  <?php endif; ?>
  <hr>
  <div class="center"><?php echo $paid ? htmlspecialchars($receipt['footer']) : '-- Phiếu tạm tính, chưa phải hóa đơn --'; ?></div>
