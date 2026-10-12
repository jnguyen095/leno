<?php // Nút thao tác một bảng giá: bật/tắt, sửa, xoá. Biến: $b. ?>
<?php echo form_open('me/price-books/'.$b['id'].'/toggle', array('class' => 'd-inline')); ?>
  <button class="btn btn-sm btn-light" title="<?php echo $b['status'] === 'ACTIVE' ? 'Tắt bảng giá' : 'Bật bảng giá'; ?>">
    <i class="bi <?php echo $b['status'] === 'ACTIVE' ? 'bi-toggle-on text-success' : 'bi-toggle-off'; ?>"></i>
  </button>
<?php echo form_close(); ?>
<a href="<?php echo site_url('me/price-books/'.$b['id'].'/edit'); ?>" class="btn btn-sm btn-light" title="Sửa"><i class="bi bi-pencil"></i></a>
<?php echo form_open('me/price-books/'.$b['id'].'/delete', array('class' => 'd-inline js-pb-delete', 'data-name' => $b['name'])); ?>
  <button class="btn btn-sm btn-light text-danger" title="Xoá"><i class="bi bi-trash"></i></button>
<?php echo form_close(); ?>
