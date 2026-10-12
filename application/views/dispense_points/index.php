<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Điểm xuất kho</h4>
    <a href="<?php echo site_url('me/inventory/dispense-points/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm</a>
  </div>
  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>Tên</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($dispense_points as $d): ?>
        <tr>
          <td><?php echo htmlspecialchars($d['name']); ?></td>
          <td><span class="badge bg-<?php echo $d['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $d['status']; ?></span></td>
          <td>
            <a href="<?php echo site_url('me/inventory/dispense-points/'.$d['id'].'/edit'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa điểm xuất kho"><i class="bi bi-pencil"></i></a>
            <?php echo form_open('me/inventory/dispense-points/'.$d['id'].'/delete', array('class'=>'d-inline', 'data-confirm' => html_escape('Ẩn điểm xuất kho "'.$d['name'].'"?'), 'data-confirm-ok' => 'Ẩn')); ?>
              <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Ẩn điểm xuất kho"><i class="bi bi-eye-slash"></i></button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
