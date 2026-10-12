<?php // Nút thao tác một khuyến mãi: bật/tắt, sửa, xoá. Biến: $d. ?>
<?php echo form_open('me/discounts/'.$d['id'].'/toggle', array('class' => 'd-inline')); ?>
  <button class="btn btn-sm btn-light" title="<?php echo $d['status'] === 'ACTIVE' ? 'Tắt khuyến mãi' : 'Bật khuyến mãi'; ?>">
    <i class="bi <?php echo $d['status'] === 'ACTIVE' ? 'bi-toggle-on text-success' : 'bi-toggle-off'; ?>"></i>
  </button>
<?php echo form_close(); ?>
<a href="<?php echo site_url('me/discounts/'.$d['id'].'/edit'); ?>" class="btn btn-sm btn-light" title="Sửa"><i class="bi bi-pencil"></i></a>
<?php echo form_open('me/discounts/'.$d['id'].'/delete', array('class' => 'd-inline js-dc-delete', 'data-name' => $d['name'])); ?>
  <button class="btn btn-sm btn-light text-danger" title="Xoá"><i class="bi bi-trash"></i></button>
<?php echo form_close(); ?>
