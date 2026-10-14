<?php
  // Loại thu / chi của Sổ quỹ (ADMIN): 2 cột Thu | Chi — thêm, đổi tên, ẩn / hiện. Phiếu cũ vẫn giữ loại đã ẩn.
  $groups = array('IN' => array('Loại thu', 'bi-arrow-down-circle', 'success'), 'OUT' => array('Loại chi', 'bi-arrow-up-circle', 'danger'));
?>
<div class="container-xl py-3 py-md-4" style="max-width:960px">
  <a href="<?php echo site_url('me/cashbook'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Sổ quỹ</a>
  <h4 class="fw-bold mb-1"><i class="bi bi-tags"></i> Loại thu chi</h4>
  <div class="text-muted small mb-3">Danh sách chọn khi lập phiếu thu / chi. "Bán hàng" (tiền từ đơn đã thanh toán) là loại thu cố định, không cần thêm.</div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <?php foreach ($groups as $type => $g): ?>
      <div class="col-md-6">
        <section class="settings-card h-100">
          <header class="settings-card-head">
            <span class="settings-card-icon text-<?php echo $g[2]; ?>"><i class="bi <?php echo $g[1]; ?>"></i></span>
            <div><h5><?php echo $g[0]; ?></h5><p><?php echo $type === 'IN' ? 'Nguồn tiền vào quỹ ngoài bán hàng.' : 'Các khoản tiền ra khỏi quỹ.'; ?></p></div>
          </header>
          <div class="settings-card-body">
            <?php echo form_open('me/cashbook/categories/save', array('class' => 'd-flex gap-2 mb-3')); ?>
              <input type="hidden" name="type" value="<?php echo $type; ?>">
              <input type="text" name="name" class="form-control" maxlength="100" required placeholder="<?php echo $type === 'IN' ? 'Thêm loại thu…' : 'Thêm loại chi…'; ?>">
              <button class="btn btn-<?php echo $g[2]; ?> text-nowrap"><i class="bi bi-plus-lg"></i> Thêm</button>
            <?php echo form_close(); ?>
            <?php if ($type === 'IN'): ?>
              <div class="cb-cat-row is-fixed"><span><i class="bi bi-lock"></i> Bán hàng <small class="text-muted">(tự động từ đơn hàng)</small></span></div>
            <?php endif; ?>
            <?php foreach ($categories as $c): if ($c['type'] !== $type) continue; $off = $c['status'] !== 'ACTIVE'; ?>
              <div class="cb-cat-row <?php echo $off ? 'is-off' : ''; ?>">
                <?php echo form_open('me/cashbook/categories/save', array('class' => 'd-flex gap-2 flex-grow-1 cb-cat-form')); ?>
                  <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                  <input type="text" name="name" class="form-control form-control-sm" maxlength="100" required value="<?php echo htmlspecialchars($c['name']); ?>">
                  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Lưu tên"><i class="bi bi-check2"></i></button>
                <?php echo form_close(); ?>
                <?php echo form_open('me/cashbook/categories/'.$c['id'].'/toggle', array('class' => 'd-inline')); ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="<?php echo $off ? 'Đang ẩn — bấm để hiện lại' : 'Ẩn khỏi danh sách chọn'; ?>">
                    <i class="bi <?php echo $off ? 'bi-eye' : 'bi-eye-slash'; ?>"></i>
                  </button>
                <?php echo form_close(); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>
    <?php endforeach; ?>
  </div>
</div>
