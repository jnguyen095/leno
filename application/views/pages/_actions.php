<?php
  // Nút thao tác một trang / tin: xem trên website, bật/tắt, sửa, xoá (rê chuột vào icon hiện chú thích).
  // Biến: $p, $base (me/pages | me/news), $labels.
  $noun = $labels['noun'];
?>
<a href="<?php echo site_url(Page_model::public_path($p)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light" data-bs-toggle="tooltip"
   title="<?php echo (int) $p['status'] && ! Page_model::is_scheduled($p) ? 'Xem trên website' : 'Xem trước (khách chưa xem được)'; ?>"><i class="bi bi-box-arrow-up-right"></i></a>
<?php echo form_open($base.'/'.$p['id'].'/toggle', array('class' => 'd-inline')); ?>
  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip"
          title="<?php echo (int) $p['status'] ? 'Đang bật — bấm để tắt '.$noun : 'Đang tắt — bấm để bật '.$noun; ?>">
    <i class="bi <?php echo (int) $p['status'] ? 'bi-toggle-on text-success' : 'bi-toggle-off'; ?>"></i>
  </button>
<?php echo form_close(); ?>
<a href="<?php echo site_url($base.'/'.$p['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="<?php echo $labels['edit']; ?>"><i class="bi bi-pencil"></i></a>
<?php echo form_open($base.'/'.$p['id'].'/delete', array('class' => 'd-inline', 'data-confirm' => html_escape('Xoá '.$noun.' "'.$p['title'].'"?'), 'data-confirm-message' => html_escape('Link /'.Page_model::public_path($p).'.html sẽ không còn mở được. Ảnh đã tải lên vẫn giữ trong Thư viện ảnh.'))); ?>
  <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá <?php echo $noun; ?>"><i class="bi bi-trash"></i></button>
<?php echo form_close(); ?>
