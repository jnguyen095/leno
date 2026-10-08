<?php
  // Lịch sử "Thông báo" (báo bếp) của đơn — giống "Lịch sử báo bếp" của ứng dụng POS: mỗi lần một thẻ
  // (giờ · người báo, MÓN MỚI / ĐỔI GHI CHÚ / HỦY) + nút "In lại". Biến: $history, $order, $table_label.
  $line_html = function ($l, $show_old) {
    $h = '<div class="kh-line"><b>'.(int) $l['qty'].' ×</b> '.htmlspecialchars($l['product_name']);
    if ($show_old && $l['old_note'] !== NULL && $l['old_note'] !== '') $h .= '<div class="kh-sub text-decoration-line-through">(cũ: '.htmlspecialchars($l['old_note']).')</div>';
    if ($l['note'] !== NULL && $l['note'] !== '') $h .= '<div class="kh-sub fst-italic">'.htmlspecialchars($l['note']).'</div>';
    elseif ($show_old) $h .= '<div class="kh-sub fst-italic">(bỏ ghi chú)</div>';
    return $h.'</div>';
  };
  $sections = array(
    array('send', 'MÓN MỚI', 'text-brand', FALSE),
    array('changed', 'ĐỔI GHI CHÚ', 'text-warning-emphasis', TRUE),
    array('cancel', 'HỦY', 'text-danger', FALSE),
  );
?>
<?php if (empty($history)): ?>
  <div class="text-muted text-center py-5">Đơn này chưa báo bếp lần nào.</div>
<?php endif; ?>
<?php foreach ($history as $h): ?>
  <div class="kh-card">
    <div class="d-flex align-items-center gap-2 mb-1">
      <i class="bi bi-clock text-muted"></i>
      <div class="fw-semibold flex-grow-1"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?><?php if ($h['staff']): ?> · <?php echo htmlspecialchars($h['staff']); ?><?php endif; ?></div>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="posPrintSlip('kh-<?php echo $h['id']; ?>')"><i class="bi bi-printer"></i> In lại</button>
    </div>
    <?php foreach ($sections as $sec): if (empty($h[$sec[0]])) continue; ?>
      <div class="kh-section">
        <div class="kh-title <?php echo $sec[2]; ?>"><?php echo $sec[1]; ?></div>
        <?php foreach ($h[$sec[0]] as $l) echo $line_html($l, $sec[3]); ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<?php // Phiếu bếp ẩn của từng lần — "In lại" in đúng phiếu đó. ?>
<div id="kitchenHistorySlips">
<?php foreach ($history as $h): ?>
  <?php $this->load->view('orders/_kitchen_slip', array('slip' => $h, 'slip_name' => 'kh-'.$h['id'])); ?>
<?php endforeach; ?>
</div>
