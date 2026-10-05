<?php
  $groups = array();
  foreach ($menu_items as $mi)
  {
      $groups[(string) $mi['group_label']][] = $mi;
  }
?>
<div class="container py-3 py-md-4" style="max-width:700px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-shield-lock"></i> Gán menu theo vai trò</h4>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <ul class="nav nav-pills mb-3">
    <?php foreach ($roles as $r): ?>
      <li class="nav-item">
        <a class="nav-link <?php echo $r === $role ? 'active' : ''; ?>" href="<?php echo site_url('me/menu-permissions').'?role='.$r; ?>"><?php echo role_label($r); ?></a>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($role === 'ADMIN'): ?>
    <div class="alert alert-info py-2 small">Vai trò Quản trị viên luôn có toàn quyền truy cập mọi nơi, không cần gán menu.</div>
  <?php else: ?>
    <?php echo form_open('me/menu-permissions', array('class' => 'card border-0 shadow-sm rounded-4')); ?>
      <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
      <div class="card-body">
        <?php foreach ($groups as $group_label => $items): ?>
          <h6 class="fw-bold mt-3 mb-2 <?php echo $group_label === '' ? 'd-none' : ''; ?>"><?php echo htmlspecialchars($group_label); ?></h6>
          <div class="row">
          <?php foreach ($items as $mi): ?>
            <div class="col-sm-6">
              <div class="form-check">
                <input type="checkbox" name="menu_item_ids[]" value="<?php echo $mi['id']; ?>" class="form-check-input" id="mi<?php echo $mi['id']; ?>" <?php echo isset($granted[$mi['id']]) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="mi<?php echo $mi['id']; ?>"><?php echo htmlspecialchars($mi['label']); ?></label>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer bg-white border-0 p-3 pt-0">
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu menu cho <?php echo role_label($role); ?></button>
      </div>
    <?php echo form_close(); ?>
  <?php endif; ?>

  <div class="mt-3">
    <a href="<?php echo site_url('me/menu-permissions/user'); ?>"><i class="bi bi-person-plus"></i> Cấp thêm menu riêng cho 1 nhân viên</a>
  </div>
</div>
