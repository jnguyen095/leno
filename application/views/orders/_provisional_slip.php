<?php
  // Phiếu tạm tính K80 (in ngay trên trang). Render lại sau mỗi thay đổi món qua AJAX để luôn khớp đơn.
  // Biến: $order, $active_items, $bank_qr. Bố cục chung với hóa đơn: _bill_body.php.
?>
<div class="print-slip receipt-k80" data-slip="provisional">
  <?php $this->load->view('orders/_bill_body', array('order' => $order, 'items' => $active_items, 'payment' => NULL, 'bank_qr' => isset($bank_qr) ? $bank_qr : NULL)); ?>
</div>
