<div class="container py-3 py-md-4" style="max-width:480px;">
  <h4 class="fw-bold mb-3"><?php echo $page_title; ?></h4>
  <?php if ( ! empty($error)): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
  <?php echo form_open(current_url()); ?>
    <div class="mb-3">
      <label class="form-label">Tên đăng nhập</label>
      <input type="text" name="username" class="form-control" required value="<?php echo $user ? htmlspecialchars($user['username']) : ''; ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Họ tên</label>
      <input type="text" name="fullname" class="form-control" required value="<?php echo $user ? htmlspecialchars($user['fullname']) : ''; ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Mật khẩu <?php echo $user ? '(để trống nếu không đổi)' : ''; ?></label>
      <input type="password" name="password" class="form-control" <?php echo $user ? '' : 'required'; ?>>
    </div>
    <div class="mb-3">
      <label class="form-label">Vai trò</label>
      <select name="role" class="form-select">
        <?php foreach (array('STAFF','BARISTA','CASHIER','ADMIN','STOCKTAKER') as $r): ?>
          <option value="<?php echo $r; ?>" <?php echo ($user && $user['role']===$r) ? 'selected' : ''; ?>><?php echo role_label($r); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-sm-6">
        <label class="form-label">Ngày bắt đầu làm việc</label>
        <input type="date" name="start_date" class="form-control" value="<?php echo $user && $user['start_date'] ? $user['start_date'] : ''; ?>">
      </div>
      <div class="col-sm-6">
        <label class="form-label">Ngày nghỉ việc</label>
        <input type="date" name="end_date" class="form-control" value="<?php echo $user && $user['end_date'] ? $user['end_date'] : ''; ?>">
      </div>
    </div>
    <?php if ($user): ?>
    <div class="mb-3">
      <label class="form-label">Trạng thái</label>
      <select name="status" class="form-select">
        <option value="ACTIVE" <?php echo $user['status']==='ACTIVE'?'selected':''; ?>>Hoạt động</option>
        <option value="INACTIVE" <?php echo $user['status']==='INACTIVE'?'selected':''; ?>>Vô hiệu hóa</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="d-grid gap-2">
      <button class="btn btn-brand btn-lg">Lưu</button>
      <a href="<?php echo site_url('me/users'); ?>" class="btn btn-outline-secondary">Hủy</a>
    </div>
  <?php echo form_close(); ?>
</div>
