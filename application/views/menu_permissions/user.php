<?php
  $groups = array();
  foreach ($menu_items as $mi)
  {
      $groups[(string) $mi['group_label']][] = $mi;
  }
?>
<div class="container py-3 py-md-4" style="max-width:700px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-person-plus"></i> Cấp thêm menu riêng cho nhân viên</h4>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Chọn nhân viên</label>
    <select class="form-select" onchange="if(this.value) location.href='<?php echo base_url('me/menu-permissions/user'); ?>/'+this.value+'.html';">
      <option value="">-- Chọn nhân viên --</option>
      <?php foreach ($users as $u): ?>
        <option value="<?php echo $u['id']; ?>" <?php echo ($target_user && (int) $target_user['id'] === (int) $u['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($u['fullname']); ?> (<?php echo role_label($u['role']); ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if ($target_user): ?>
    <p class="text-muted small">Các mục đã tô xám là quyền có sẵn theo vai trò <strong><?php echo role_label($target_user['role']); ?></strong> — không cần cấp lại. Tích thêm các mục khác để cấp riêng cho <strong><?php echo htmlspecialchars($target_user['fullname']); ?></strong>.</p>

    <?php echo form_open('me/menu-permissions/user', array('class' => 'card border-0 shadow-sm rounded-4')); ?>
      <input type="hidden" name="user_id" value="<?php echo $target_user['id']; ?>">
      <div class="card-body">
        <?php foreach ($groups as $group_label => $items): ?>
          <h6 class="fw-bold mt-3 mb-2 <?php echo $group_label === '' ? 'd-none' : ''; ?>"><?php echo htmlspecialchars($group_label); ?></h6>
          <div class="row">
          <?php foreach ($items as $mi): $from_role = isset($role_granted[$mi['id']]); $from_user = isset($user_granted[$mi['id']]); ?>
            <div class="col-sm-6">
              <div class="form-check">
                <input type="checkbox" name="menu_item_ids[]" value="<?php echo $mi['id']; ?>" class="form-check-input" id="umi<?php echo $mi['id']; ?>"
                  <?php echo ($from_role || $from_user) ? 'checked' : ''; ?> <?php echo $from_role ? 'disabled' : ''; ?>>
                <label class="form-check-label <?php echo $from_role ? 'text-muted' : ''; ?>" for="umi<?php echo $mi['id']; ?>">
                  <?php echo htmlspecialchars($mi['label']); ?><?php echo $from_role ? ' (theo vai trò)' : ''; ?>
                </label>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer bg-white border-0 p-3 pt-0">
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu</button>
      </div>
    <?php echo form_close(); ?>
  <?php endif; ?>
</div>
