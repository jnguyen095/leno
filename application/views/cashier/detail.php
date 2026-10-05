<div class="container py-3 py-md-4" style="max-width:560px;">
  <a href="<?php echo site_url('me/cashier'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Danh sách</a>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-header bg-white d-flex justify-content-between">
      <span class="fw-semibold"><?php echo $order['table_name'] ? htmlspecialchars($order['table_name']) : '<i class="bi bi-bag-check"></i> Mang đi'; ?> — #<?php echo htmlspecialchars($order['order_no']); ?></span>
      <span class="badge bg-<?php echo order_status_badge($order['status']); ?>"><?php echo $order['status']; ?></span>
    </div>
    <div class="list-group list-group-flush">
      <?php foreach ($items as $it): ?>
      <div class="list-group-item d-flex justify-content-between">
        <span><?php echo $it['qty']; ?>x <?php echo htmlspecialchars($it['product_name']); ?></span>
        <span><?php echo money_format_vnd($it['amount']); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer bg-white">
      <div class="d-flex justify-content-between small"><span>Tạm tính</span><span><?php echo money_format_vnd($order['subtotal']); ?></span></div>
      <div class="d-flex justify-content-between small"><span>Giảm giá</span><span>-<?php echo money_format_vnd($order['discount_amount']); ?></span></div>
      <div class="d-flex justify-content-between small"><span>VAT</span><span><?php echo money_format_vnd($order['vat_amount']); ?></span></div>
      <div class="d-flex justify-content-between fw-bold fs-4 mt-1"><span>Tổng cộng</span><span class="text-brand"><?php echo money_format_vnd($order['total_amount']); ?></span></div>
    </div>
  </div>

  <?php if ($order['status'] === 'OPEN'): ?>
    <div class="alert alert-warning">Đơn chưa đóng bill. <a href="<?php echo site_url('me/cashier'); ?>">Quay lại</a> để đóng bill trước.</div>
  <?php elseif ($order['status'] === 'WAIT_PAYMENT'): ?>
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <h6 class="fw-semibold mb-3">Thanh toán</h6>
        <?php echo form_open('me/cashier/'.$order['id'].'/pay'); ?>
          <div class="mb-3">
            <label class="form-label">Phương thức</label>
            <select name="payment_method" id="paymentMethod" class="form-select form-select-lg">
              <option value="CASH">Tiền mặt</option>
              <option value="CARD">Thẻ</option>
              <option value="TRANSFER">Chuyển khoản</option>
              <option value="QR">QR Pay</option>
            </select>
          </div>
          <div class="mb-3" id="receivedGroup">
            <label class="form-label">Khách đưa</label>
            <input type="number" name="received_amount" id="receivedAmount" class="form-control form-control-lg" value="<?php echo (int) $order['total_amount']; ?>">
          </div>
          <div class="mb-3 fs-5">
            Tiền thối lại: <span class="fw-bold text-success" id="changeAmount">0đ</span>
          </div>
          <button class="btn btn-brand btn-lg w-100">Xác nhận thanh toán</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  <?php elseif ($order['status'] === 'PAID'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Đã thanh toán<?php echo $order['table_name'] ? ' — bàn đã tự động về trống.' : '.'; ?></div>
    <div class="d-grid gap-2">
      <a href="<?php echo site_url('me/cashier/'.$order['id'].'/invoice'); ?>" target="_blank" class="btn btn-brand btn-lg"><i class="bi bi-printer"></i> In hóa đơn</a>
    </div>
  <?php endif; ?>
</div>
<script>
var total = <?php echo (float) $order['total_amount']; ?>;
var receivedInput = document.getElementById('receivedAmount');
var changeSpan = document.getElementById('changeAmount');
function calcChange(){
  var received = parseFloat(receivedInput.value) || 0;
  var change = Math.max(0, received - total);
  changeSpan.textContent = change.toLocaleString('vi-VN') + 'đ';
}
if (receivedInput){
  //receivedInput.addEventListener('input', calcChange);
  //calcChange();
}
</script>
