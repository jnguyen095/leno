<?php
  // Quản trị → Khuyến mãi: danh sách + trạng thái hiệu lực hôm nay. Thẻ trên điện thoại, bảng trên máy tính.
  $today = date('Y-m-d');
  $state = function ($d) use ($applicable, $today) {
    if ($d['status'] !== 'ACTIVE') return array('Đang tắt', 'off');
    if (in_array($d['id'], $applicable)) return array('Áp dụng hôm nay', 'on');
    if ($d['end_date'] && $d['end_date'] < $today) return array('Đã hết hạn', 'off');
    if ($d['start_date'] > $today) return array('Chưa bắt đầu', 'wait');
    return array('Không áp dụng hôm nay', 'wait');
  };
  $range = function ($d) {
    return date('d/m/Y', strtotime($d['start_date'])).' → '.($d['end_date'] ? date('d/m/Y', strtotime($d['end_date'])) : 'không giới hạn');
  };
  $rule = function ($d) { return Discount_model::describe($d['discount_type'], $d['value'], $d['max_discount'], $d['min_order']); };
?>
<div class="container-xl py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-percent"></i> Khuyến mãi</h4>
      <div class="text-muted small">Giảm trên tổng đơn (% hoặc số tiền). Trên POS người bán tự chọn khuyến mãi cho đơn.</div>
    </div>
    <a href="<?php echo site_url('me/discounts/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm khuyến mãi</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <?php if (empty($discounts)): ?>
    <div class="orders-card text-center text-muted py-5">
      <i class="bi bi-percent fs-1 d-block mb-2 opacity-50"></i>
      Chưa có khuyến mãi nào. Bấm <b>Thêm khuyến mãi</b>, ví dụ "Giảm 15% đơn từ 500.000".
    </div>
  <?php else: ?>
  <div class="orders-card">
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table">
        <thead><tr><th>Tên khuyến mãi</th><th>Mức giảm</th><th>Thời gian</th><th>Áp dụng</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($discounts as $d): $st = $state($d); ?>
          <tr>
            <td><a href="<?php echo site_url('me/discounts/'.$d['id'].'/edit'); ?>" class="fw-semibold text-body text-decoration-none"><?php echo htmlspecialchars($d['name']); ?></a></td>
            <td class="small"><?php echo $rule($d); ?></td>
            <td class="small"><?php echo $range($d); ?></td>
            <td class="small"><?php echo Price_book_model::weekdays_label($d['weekdays']); ?></td>
            <td><span class="pb-state pb-state-<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></td>
            <td class="text-end text-nowrap"><?php $this->load->view('discounts/_actions', array('d' => $d)); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-md-none orders-list">
      <?php foreach ($discounts as $d): $st = $state($d); ?>
        <div class="orders-item">
          <a href="<?php echo site_url('me/discounts/'.$d['id'].'/edit'); ?>" class="orders-item-main">
            <div class="fw-semibold"><?php echo htmlspecialchars($d['name']); ?></div>
            <div class="small"><?php echo $rule($d); ?></div>
            <div class="small text-muted"><?php echo $range($d); ?> · <?php echo Price_book_model::weekdays_label($d['weekdays']); ?></div>
            <div class="mt-1"><span class="pb-state pb-state-<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></div>
          </a>
          <div class="text-nowrap"><?php $this->load->view('discounts/_actions', array('d' => $d)); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
