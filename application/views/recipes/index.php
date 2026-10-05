<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-egg-fried"></i> Công thức pha chế</h4>
    <a href="<?php echo site_url('me/recipes/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm công thức</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>Món</th><th class="text-end">Tổng cost</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recipes as $r): ?>
        <tr>
          <td><a href="<?php echo site_url('me/recipes/'.$r['id'].'/edit'); ?>" class="fw-semibold text-decoration-none"><?php echo htmlspecialchars($r['name']); ?></a></td>
          <td class="text-end fw-semibold text-brand">
            <?php echo ! $r['cost_complete'] ? '<span class="text-warning-emphasis" title="Chưa đủ dữ liệu giá">~ </span>' : ''; ?><?php echo money_format_vnd($r['total_cost']); ?>
          </td>
          <td><span class="badge bg-<?php echo $r['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $r['status']==='ACTIVE'?'Hoạt động':'Ẩn'; ?></span></td>
          <td class="text-nowrap">
            <a href="<?php echo site_url('me/recipes/'.$r['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
            <?php echo form_open('me/recipes/'.$r['id'].'/delete', array('class' => 'd-inline', 'onsubmit' => "return confirm('Xóa công thức này? Toàn bộ thành phần cũng sẽ bị xóa.');")); ?>
              <button class="btn btn-sm btn-outline-danger">Xóa</button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recipes)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">Chưa có công thức nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
