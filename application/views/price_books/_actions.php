<?php // Nút thao tác một bảng giá: bật/tắt, sửa, xoá. Biến: $b. ?>
<?php echo form_open('me/price-books/'.$b['id'].'/toggle', array('class' => 'd-inline')); ?>
  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="<?php echo $b['status'] === 'ACTIVE' ? 'Đang bật — bấm để tắt bảng giá' : 'Đang tắt — bấm để bật bảng giá'; ?>">
    <i class="bi <?php echo $b['status'] === 'ACTIVE' ? 'bi-toggle-on text-success' : 'bi-toggle-off'; ?>"></i>
  </button>
<?php echo form_close(); ?>
<a href="<?php echo site_url('me/price-books/'.$b['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa bảng giá"><i class="bi bi-pencil"></i></a>
<?php echo form_open('me/price-books/'.$b['id'].'/delete', array('class' => 'd-inline', 'data-confirm' => html_escape('Xoá bảng giá "'.$b['name'].'"?'), 'data-confirm-message' => html_escape('Đơn đã bán theo bảng giá này vẫn giữ nguyên giá.'))); ?>
  <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá bảng giá"><i class="bi bi-trash"></i></button>
<?php echo form_close(); ?>
