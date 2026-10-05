<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Người dùng</h4>
    <a href="<?php echo site_url('me/users/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm</a>
  </div>

  <?php echo form_open('me/users', array('method' => 'get', 'class' => 'row g-2 mb-3')); ?>
    <div class="col-auto">
      <input type="text" name="q" class="form-control" placeholder="Tìm theo tên đăng nhập hoặc họ tên..." value="<?php echo htmlspecialchars($keyword); ?>" style="min-width:220px;">
    </div>
    <div class="col-auto">
      <select name="role" class="form-select" onchange="this.form.submit()">
        <option value="">Tất cả vai trò</option>
        <?php foreach (array('STAFF','BARISTA','CASHIER','ADMIN','STOCKTAKER') as $r): ?>
          <option value="<?php echo $r; ?>" <?php echo $role === $r ? 'selected' : ''; ?>><?php echo role_label($r); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">Tất cả trạng thái</option>
        <option value="ACTIVE" <?php echo $status === 'ACTIVE' ? 'selected' : ''; ?>>ACTIVE</option>
        <option value="INACTIVE" <?php echo $status === 'INACTIVE' ? 'selected' : ''; ?>>INACTIVE</option>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Tìm</button>
    </div>
    <?php if ($role || $status || $keyword): ?>
    <div class="col-auto">
      <a href="<?php echo site_url('me/users'); ?>" class="btn btn-outline-secondary">Xoá lọc</a>
    </div>
    <?php endif; ?>
  <?php echo form_close(); ?>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light"><tr><th>STT</th><th>Tên đăng nhập</th><th>Họ tên</th><th>Vai trò</th><th>Ngày bắt đầu</th><th>Ngày nghỉ</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php $stt = 1; foreach ($users as $u): ?>
        <tr>
          <td><?php echo $stt++; ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?></td>
          <td><?php echo htmlspecialchars($u['fullname']); ?></td>
          <td><?php echo role_label($u['role']); ?></td>
          <td><?php echo $u['start_date'] ? date('d/m/Y', strtotime($u['start_date'])) : '—'; ?></td>
          <td><?php echo $u['end_date'] ? date('d/m/Y', strtotime($u['end_date'])) : '—'; ?></td>
          <td><span class="badge bg-<?php echo $u['status']==='ACTIVE'?'success':'secondary'; ?>"><?php echo $u['status']; ?></span></td>
          <td>
            <a href="<?php echo site_url('me/users/'.$u['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
            <?php if ((int)$u['id'] !== (int)$current_user['id']): ?>
            <?php echo form_open('me/users/'.$u['id'].'/delete', array('class'=>'d-inline', 'onsubmit'=>"return confirm('Vô hiệu hóa tài khoản này?');")); ?>
              <button class="btn btn-sm btn-outline-danger">Vô hiệu hóa</button>
            <?php echo form_close(); ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
