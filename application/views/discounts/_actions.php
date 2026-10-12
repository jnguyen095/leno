<?php // Nút thao tác một khuyến mãi: bật/tắt, sửa, xoá (rê chuột vào icon hiện chú thích). Biến: $d. ?>
<?php echo form_open('me/discounts/'.$d['id'].'/toggle', array('class' => 'd-inline')); ?>
  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip"
          title="<?php echo $d['status'] === 'ACTIVE' ? 'Đang bật — bấm để tắt khuyến mãi' : 'Đang tắt — bấm để bật khuyến mãi'; ?>">
    <i class="bi <?php echo $d['status'] === 'ACTIVE' ? 'bi-toggle-on text-success' : 'bi-toggle-off'; ?>"></i>
  </button>
<?php echo form_close(); ?>
<a href="<?php echo site_url('me/discounts/'.$d['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa khuyến mãi"><i class="bi bi-pencil"></i></a>
<?php echo form_open('me/discounts/'.$d['id'].'/delete', array('class' => 'd-inline', 'data-confirm' => html_escape('Xoá khuyến mãi "'.$d['name'].'"?'), 'data-confirm-message' => html_escape('Đơn đã áp khuyến mãi này vẫn giữ nguyên.'))); ?>
  <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá khuyến mãi"><i class="bi bi-trash"></i></button>
<?php echo form_close(); ?>
