<?php
  // Quản trị → Bảng giá: danh sách bảng giá + trạng thái hiệu lực hôm nay. Thẻ trên điện thoại, bảng trên máy tính.
  $today = date('Y-m-d');
  $state = function ($b) use ($applicable, $today) {
    if ($b['status'] !== 'ACTIVE') return array('Đang tắt', 'off');
    if (in_array($b['id'], $applicable)) return array('Áp dụng hôm nay', 'on');
    if ($b['end_date'] && $b['end_date'] < $today) return array('Đã hết hạn', 'off');
    if ($b['start_date'] > $today) return array('Chưa bắt đầu', 'wait');
    return array('Không áp dụng hôm nay', 'wait');
  };
  $range = function ($b) {
    return date('d/m/Y', strtotime($b['start_date'])).' → '.($b['end_date'] ? date('d/m/Y', strtotime($b['end_date'])) : 'không giới hạn');
  };
?>
<div class="container-xl py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-tags"></i> Bảng giá</h4>
      <div class="text-muted small">Giá bán riêng theo dịp (cuối tuần, ưu đãi...). Trên POS người bán tự chọn bảng giá cho đơn.</div>
    </div>
    <a href="<?php echo site_url('me/price-books/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm bảng giá</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <?php if (empty($books)): ?>
    <div class="orders-card text-center text-muted py-5">
      <i class="bi bi-tags fs-1 d-block mb-2 opacity-50"></i>
      Chưa có bảng giá nào. Bấm <b>Thêm bảng giá</b> để tạo, ví dụ "Giá cuối tuần" áp dụng T7, CN.
    </div>
  <?php else: ?>
  <div class="orders-card">
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table">
        <thead><tr><th>Tên bảng giá</th><th>Thời gian</th><th>Áp dụng</th><th class="text-center">Số món</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($books as $b): $st = $state($b); ?>
          <tr>
            <td><a href="<?php echo site_url('me/price-books/'.$b['id'].'/edit'); ?>" class="fw-semibold text-body text-decoration-none"><?php echo htmlspecialchars($b['name']); ?></a></td>
            <td class="small"><?php echo $range($b); ?></td>
            <td class="small"><?php echo Price_book_model::weekdays_label($b['weekdays']); ?></td>
            <td class="text-center"><?php echo (int) $b['item_count']; ?></td>
            <td><span class="pb-state pb-state-<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></td>
            <td class="text-end text-nowrap">
              <?php $this->load->view('price_books/_actions', array('b' => $b)); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-md-none orders-list">
      <?php foreach ($books as $b): $st = $state($b); ?>
        <div class="orders-item">
          <a href="<?php echo site_url('me/price-books/'.$b['id'].'/edit'); ?>" class="orders-item-main">
            <div class="fw-semibold"><?php echo htmlspecialchars($b['name']); ?></div>
            <div class="small text-muted"><?php echo $range($b); ?> · <?php echo Price_book_model::weekdays_label($b['weekdays']); ?> · <?php echo (int) $b['item_count']; ?> món</div>
            <div class="mt-1"><span class="pb-state pb-state-<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></div>
          </a>
          <div class="text-nowrap"><?php $this->load->view('price_books/_actions', array('b' => $b)); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>
document.querySelectorAll('.js-pb-delete').forEach(function(f){
  f.addEventListener('submit', function(e){
    if ( ! confirm('Xoá bảng giá "' + f.dataset.name + '"? Đơn đã bán theo bảng giá này vẫn giữ nguyên giá.')) e.preventDefault();
  });
});
</script>
