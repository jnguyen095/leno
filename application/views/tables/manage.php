<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Quản lý bàn</h4>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/tables/manage/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm bàn</a>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo $this->session->flashdata('error'); ?></div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th class="text-end">Thứ tự</th><th>Mã bàn</th><th>Tên bàn</th><th class="text-end">Sức chứa</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($tables as $t): ?>
        <tr>
          <td class="text-end text-muted"><?php echo (int) $t['sort_order']; ?></td>
          <td><?php echo htmlspecialchars($t['table_code']); ?></td>
          <td>
            <?php echo htmlspecialchars($t['table_name']); ?>
            <?php if ( ! empty($t['note'])): ?><div class="small text-muted"><i class="bi bi-sticky"></i> <?php echo htmlspecialchars($t['note']); ?></div><?php endif; ?>
          </td>
          <td class="text-end"><?php echo $t['capacity']; ?></td>
          <td><span class="badge bg-<?php echo table_status_badge($t['status']); ?>"><?php echo $t['status']; ?></span></td>
          <td class="text-nowrap">
            <a href="<?php echo site_url('me/tables/manage/'.$t['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
            <?php if ($t['status'] !== 'AVAILABLE'): ?>
              <?php echo form_open('me/tables/manage/'.$t['id'].'/reset-status', array('class'=>'d-inline', 'onsubmit'=>"return confirm('Bắt bàn này về trạng thái Trống? Đơn hàng đang mở (nếu có) sẽ bị hủy.');")); ?>
                <button class="btn btn-sm btn-outline-warning">Đặt lại</button>
              <?php echo form_close(); ?>
            <?php else: ?>
              <?php echo form_open('me/tables/manage/'.$t['id'].'/delete', array('class'=>'d-inline', 'onsubmit'=>"return confirm('Xóa bàn này?');")); ?>
                <button class="btn btn-sm btn-outline-danger">Xóa</button>
              <?php echo form_close(); ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($tables)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Chưa có bàn nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
