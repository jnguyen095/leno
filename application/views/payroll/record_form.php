<div class="container py-3 py-md-4" style="max-width:600px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Dữ liệu lương — <?php echo htmlspecialchars($target_user['fullname']); ?></h4>
    <a href="<?php echo site_url('me/payroll/admin').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
  </div>
  <p class="text-muted small mb-3"><?php echo payroll_period_label($period); ?> — Loại lương: <strong><?php echo $settings['salary_type'] === 'HOURLY' ? 'Theo giờ' : 'Cố định'; ?></strong></p>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 small"><?php echo $this->session->flashdata('error'); ?></div>
  <?php endif; ?>

  <?php echo form_open(current_url().'?period='.$period, array('class' => 'card border-0 shadow-sm rounded-4')); ?>
    <div class="card-body">
      <?php if ($settings['salary_type'] === 'FIXED'): ?>
        <div class="mb-3">
          <label class="form-label">Số ngày nghỉ trong tháng</label>
          <div>
            <span class="fs-5 fw-semibold"><?php echo rtrim(rtrim(number_format($salary['absence_days'], 2, '.', ''), '0'), '.'); ?> ngày</span>
            <a href="<?php echo site_url('me/payroll/hours/'.$target_user['id']).'?period='.$period; ?>" class="btn btn-sm btn-outline-primary ms-2">Chọn ngày nghỉ</a>
          </div>
          <div class="form-text">Đơn giá/ngày = lương cố định (<?php echo money_format_vnd($salary['fixed_salary']); ?>) ÷ <?php echo $salary['days_in_month']; ?> ngày = <?php echo money_format_vnd($salary['daily_rate']); ?>/ngày.</div>
        </div>
      <?php else: ?>
        <div class="mb-3">
          <label class="form-label">Giờ làm trong tháng</label>
          <div>
            <span class="fs-5 fw-semibold"><?php echo rtrim(rtrim(number_format($salary['total_hours'], 2, '.', ''), '0'), '.'); ?> giờ</span>
            <a href="<?php echo site_url('me/payroll/hours/'.$target_user['id']).'?period='.$period; ?>" class="btn btn-sm btn-outline-primary ms-2">Nhập giờ làm theo ngày</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Lương tháng (tính tự động)</label>
        <input type="text" class="form-control" value="<?php echo money_format_vnd($salary['gross_salary']); ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Thưởng (tính tự động từ danh sách bên dưới)</label>
        <input type="text" class="form-control" value="<?php echo money_format_vnd($salary['bonus_total']); ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Đã ứng lương (đ)</label>
        <input type="number" step="1000" min="0" name="advance_amount" id="advanceInput" class="form-control" value="<?php echo (float) $record['advance_amount']; ?>">
      </div>

      <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
        <span class="fw-bold">Tiền lương cần trả</span>
        <span class="fs-4 fw-bold" id="netSalary"><?php echo money_format_vnd($salary['net_salary']); ?></span>
      </div>

      <div class="mb-3">
        <label class="form-label">Trạng thái chi lương</label>
        <select name="paid_status" class="form-select">
          <option value="UNPAID" <?php echo $record['paid_status'] === 'UNPAID' ? 'selected' : ''; ?>>Chưa chi lương</option>
          <option value="PAID" <?php echo $record['paid_status'] === 'PAID' ? 'selected' : ''; ?>>Đã chi lương</option>
        </select>
      </div>

      <div class="mb-0">
        <label class="form-label">Ghi chú</label>
        <input type="text" name="note" class="form-control" value="<?php echo htmlspecialchars((string) $record['note']); ?>">
      </div>
    </div>
    <div class="card-footer bg-white border-0 p-3 pt-0">
      <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu</button>
    </div>
  <?php echo form_close(); ?>

  <div class="card border-0 shadow-sm rounded-4 mt-3">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-gift"></i> Thưởng</h6>

      <?php if (empty($bonuses)): ?>
        <p class="text-muted small">Chưa có khoản thưởng nào trong tháng này.</p>
      <?php else: ?>
        <table class="table table-sm align-middle mb-3">
          <tbody>
          <?php foreach ($bonuses as $b): ?>
            <tr>
              <td>
                <span class="fw-semibold text-success"><?php echo money_format_vnd($b['amount']); ?></span>
                <?php if ($b['bonus_type'] === 'HOURLY'): ?>
                  <span class="badge bg-info-subtle text-info-emphasis ms-1"><?php echo rtrim(rtrim(number_format($b['hours'], 2, '.', ''), '0'), '.'); ?> giờ × <?php echo money_format_vnd($b['rate']); ?></span>
                <?php endif; ?>
                <?php if ($b['note']): ?><div class="text-muted small"><?php echo htmlspecialchars($b['note']); ?></div><?php endif; ?>
              </td>
              <td class="text-end">
                <?php echo form_open('me/payroll/record/'.$target_user['id'].'/bonus/'.$b['id'].'/delete?period='.$period, array('class' => 'd-inline', 'onsubmit' => "return confirm('Xóa khoản thưởng này?');")); ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                <?php echo form_close(); ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <hr>
      <h6 class="fw-bold mb-2">Thêm khoản thưởng</h6>
      <?php echo form_open('me/payroll/record/'.$target_user['id'].'/bonus/add', array('id' => 'bonusForm')); ?>
        <input type="hidden" name="period" value="<?php echo $period; ?>">
        <div class="mb-2">
          <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="bonus_type" id="bonusTypeAmount" value="AMOUNT" checked>
            <label class="btn btn-outline-brand" for="bonusTypeAmount">Theo số tiền</label>
            <input type="radio" class="btn-check" name="bonus_type" id="bonusTypeHourly" value="HOURLY">
            <label class="btn btn-outline-brand" for="bonusTypeHourly">Theo giờ (× <?php echo money_format_vnd($settings['hourly_rate']); ?>/giờ)</label>
          </div>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-sm-6" id="bonusAmountWrap">
            <label class="form-label">Số tiền (đ)</label>
            <input type="number" step="1000" min="0" name="amount" class="form-control" placeholder="VD: 200000">
          </div>
          <div class="col-sm-6 d-none" id="bonusHoursWrap">
            <label class="form-label">Số giờ</label>
            <input type="number" step="0.5" min="0" name="hours" class="form-control" placeholder="VD: 4">
          </div>
          <div class="col-sm-6">
            <label class="form-label">Ghi chú</label>
            <input type="text" name="note" class="form-control" placeholder="VD: Thưởng dịp lễ 2/7">
          </div>
        </div>
        <button class="btn btn-outline-brand"><i class="bi bi-plus-lg"></i> Thêm thưởng</button>
      <?php echo form_close(); ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-3">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-bank"></i> Thông tin thanh toán</h6>
      <?php if ($bank_info['bank_name'] || $bank_info['bank_account_number']): ?>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted" style="width:40%;">Ngân hàng</td><td><?php echo htmlspecialchars((string) $bank_info['bank_name']); ?></td></tr>
          <tr><td class="text-muted">Số tài khoản</td><td class="fw-semibold"><?php echo htmlspecialchars((string) $bank_info['bank_account_number']); ?></td></tr>
          <tr><td class="text-muted">Chủ tài khoản</td><td><?php echo htmlspecialchars((string) $bank_info['bank_account_name']); ?></td></tr>
        </table>
      <?php else: ?>
        <p class="text-muted small mb-0">Nhân viên chưa cập nhật thông tin ngân hàng. <a href="<?php echo site_url('me/payroll/settings/'.$target_user['id']); ?>">Cập nhật ngay</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  var advanceInput = document.getElementById('advanceInput');
  var netSalaryEl = document.getElementById('netSalary');
  var grossSalary = <?php echo (float) $salary['gross_salary']; ?>;
  var bonusTotal = <?php echo (float) $salary['bonus_total']; ?>;

  function formatVnd(n){
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + 'đ';
  }
  function recalc(){
    var advance = parseFloat(advanceInput.value) || 0;
    netSalaryEl.textContent = formatVnd(grossSalary + bonusTotal - advance);
  }
  advanceInput.addEventListener('input', recalc);

  var typeAmount = document.getElementById('bonusTypeAmount');
  var typeHourly = document.getElementById('bonusTypeHourly');
  var amountWrap = document.getElementById('bonusAmountWrap');
  var hoursWrap = document.getElementById('bonusHoursWrap');
  function toggleBonusType(){
    amountWrap.classList.toggle('d-none', ! typeAmount.checked);
    hoursWrap.classList.toggle('d-none', ! typeHourly.checked);
  }
  typeAmount.addEventListener('change', toggleBonusType);
  typeHourly.addEventListener('change', toggleBonusType);
})();
</script>
