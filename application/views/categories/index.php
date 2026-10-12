<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Danh mục sản phẩm</h4>
    <a href="<?php echo site_url('me/categories/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm</a>
  </div>
  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>Tên</th><th>Thứ tự</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['name']); ?></td>
          <td><?php echo $c['sort_order']; ?></td>
          <td><span class="badge bg-<?php echo $c['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $c['status']; ?></span></td>
          <td>
            <a href="<?php echo site_url('me/categories/'.$c['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa danh mục"><i class="bi bi-pencil"></i></a>
            <?php echo form_open('me/categories/'.$c['id'].'/delete', array('class'=>'d-inline', 'data-confirm' => html_escape('Ẩn danh mục "'.$c['name'].'"?'), 'data-confirm-message' => html_escape('Danh mục bị ẩn sẽ không hiện trên thực đơn.'), 'data-confirm-ok' => 'Ẩn')); ?>
              <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Ẩn danh mục"><i class="bi bi-eye-slash"></i></button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
