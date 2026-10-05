<div class="container py-3 py-md-4" style="max-width:600px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Cấu hình lương — <?php echo htmlspecialchars($target_user['fullname']); ?></h4>
    <a href="<?php echo site_url('me/payroll/admin'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <?php echo form_open(current_url(), array('class' => 'card border-0 shadow-sm rounded-4')); ?>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Áp dụng từ tháng</label>
        <input type="month" name="effective_from" class="form-control" style="max-width:200px;" value="<?php echo date('Y-m'); ?>">
        <div class="form-text">Mức lương bên dưới chỉ áp dụng từ tháng này trở đi — các tháng trước đó vẫn giữ nguyên mức lương đã tính.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Loại lương</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="salary_type" id="typeFixed" value="FIXED" <?php echo $settings['salary_type'] === 'FIXED' ? 'checked' : ''; ?>>
          <label class="form-check-label" for="typeFixed">Cố định theo tháng</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="salary_type" id="typeHourly" value="HOURLY" <?php echo $settings['salary_type'] === 'HOURLY' ? 'checked' : ''; ?>>
          <label class="form-check-label" for="typeHourly">Theo giờ làm việc</label>
        </div>
      </div>

      <div class="mb-3" id="fixedWrap">
        <label class="form-label">Lương cố định / tháng (đ)</label>
        <input type="number" step="1000" min="0" name="fixed_salary" class="form-control" value="<?php echo (float) $settings['fixed_salary']; ?>">
      </div>

      <div class="mb-3" id="hourlyWrap">
        <label class="form-label">Đơn giá / giờ (đ)</label>
        <input type="number" step="1000" min="0" name="hourly_rate" class="form-control" value="<?php echo (float) $settings['hourly_rate']; ?>">
      </div>

      <hr>
      <h6 class="fw-bold mb-3"><i class="bi bi-bank"></i> Thông tin ngân hàng</h6>
      <div class="row g-2">
        <div class="col-sm-6">
          <label class="form-label">Ngân hàng</label>
          <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars((string) $settings['bank_name']); ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Số tài khoản</label>
          <input type="text" name="bank_account_number" class="form-control" value="<?php echo htmlspecialchars((string) $settings['bank_account_number']); ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label">Chủ tài khoản</label>
          <input type="text" name="bank_account_name" class="form-control" value="<?php echo htmlspecialchars((string) $settings['bank_account_name']); ?>">
        </div>
      </div>
    </div>
    <div class="card-footer bg-white border-0 p-3 pt-0">
      <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu cấu hình</button>
    </div>
  <?php echo form_close(); ?>

  <?php if ( ! empty($rate_history)): ?>
  <div class="card border-0 shadow-sm rounded-4 mt-3">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-clock-history"></i> Lịch sử mức lương</h6>
      <table class="table table-sm mb-0">
        <thead class="text-muted small"><tr><th>Áp dụng từ</th><th>Loại lương</th><th class="text-end">Mức lương</th></tr></thead>
        <tbody>
        <?php foreach ($rate_history as $h): ?>
          <tr>
            <td><?php echo payroll_period_label($h['effective_from']); ?></td>
            <td><?php echo $h['salary_type'] === 'HOURLY' ? 'Theo giờ' : 'Cố định'; ?></td>
            <td class="text-end">
              <?php echo $h['salary_type'] === 'HOURLY' ? money_format_vnd($h['hourly_rate']).'/giờ' : money_format_vnd($h['fixed_salary']).'/tháng'; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
(function(){
  var typeFixed = document.getElementById('typeFixed');
  var typeHourly = document.getElementById('typeHourly');
  var fixedWrap = document.getElementById('fixedWrap');
  var hourlyWrap = document.getElementById('hourlyWrap');

  function toggle(){
    fixedWrap.style.display = typeFixed.checked ? '' : 'none';
    hourlyWrap.style.display = typeHourly.checked ? '' : 'none';
  }
  typeFixed.addEventListener('change', toggle);
  typeHourly.addEventListener('change', toggle);
  toggle();
})();
</script>
