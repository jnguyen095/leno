<?php // Nút thao tác một trang: xem trên website, bật/tắt, sửa, xoá (rê chuột vào icon hiện chú thích). Biến: $p. ?>
<a href="<?php echo site_url('page/'.$p['code']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light" data-bs-toggle="tooltip"
   title="<?php echo (int) $p['status'] ? 'Xem trên website' : 'Xem trước (trang đang tắt, khách chưa xem được)'; ?>"><i class="bi bi-box-arrow-up-right"></i></a>
<?php echo form_open('me/pages/'.$p['id'].'/toggle', array('class' => 'd-inline')); ?>
  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip"
          title="<?php echo (int) $p['status'] ? 'Đang bật — bấm để tắt trang' : 'Đang tắt — bấm để bật trang'; ?>">
    <i class="bi <?php echo (int) $p['status'] ? 'bi-toggle-on text-success' : 'bi-toggle-off'; ?>"></i>
  </button>
<?php echo form_close(); ?>
<a href="<?php echo site_url('me/pages/'.$p['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa trang"><i class="bi bi-pencil"></i></a>
<?php echo form_open('me/pages/'.$p['id'].'/delete', array('class' => 'd-inline', 'data-confirm' => html_escape('Xoá trang "'.$p['title'].'"?'), 'data-confirm-message' => html_escape('Link /page/'.$p['code'].'.html sẽ không còn mở được. Ảnh đã tải lên vẫn giữ trong Thư viện ảnh.'))); ?>
  <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá trang"><i class="bi bi-trash"></i></button>
<?php echo form_close(); ?>
