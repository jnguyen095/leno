<?php
  // Hóa đơn K80 — dùng chung cho trang in riêng (orders/invoice.php) và in tự động ngay sau khi
  // Thanh toán (tables/index.php). Biến: $order, $items, $payment. Bố cục chung: _bill_body.php.
  $this->load->view('orders/_bill_body', array('order' => $order, 'items' => $items, 'payment' => $payment));
