<?php
  $prev_period = date('Y-m', strtotime($period.'-01 -1 month'));
  $next_period = date('Y-m', strtotime($period.'-01 +1 month'));
?>
<div class="container py-3 py-md-4" style="max-width:500px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?php echo $is_hourly ? 'Giờ làm' : 'Ngày nghỉ'; ?> — <?php echo htmlspecialchars($target_user['fullname']); ?></h4>
    <a href="<?php echo site_url('me/payroll/admin').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <?php if ( ! $is_hourly): ?>
    <p class="text-muted small">Chọn những ngày nhân viên nghỉ cả ngày hoặc nửa ngày — tổng số ngày nghỉ sẽ được dùng để trừ vào lương cố định.</p>
  <?php endif; ?>

  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
    <a href="<?php echo site_url('me/payroll/hours/'.$target_user['id']).'?period='.$prev_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-semibold"><?php echo payroll_period_label($period); ?></span>
    <a href="<?php echo site_url('me/payroll/hours/'.$target_user['id']).'?period='.$next_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
  </div>

  <?php if ($target_user['start_date'] || $target_user['end_date']): ?>
    <div class="small text-muted mb-3">
      <i class="bi bi-calendar-range"></i> Thời gian làm việc:
      <?php echo $target_user['start_date'] ? date('d/m/Y', strtotime($target_user['start_date'])) : '—'; ?>
      &rarr;
      <?php echo $target_user['end_date'] ? date('d/m/Y', strtotime($target_user['end_date'])) : 'hiện tại'; ?>
    </div>
  <?php endif; ?>

  <?php if ( ! $has_range_day): ?>
    <div class="alert alert-warning py-2 small">Nhân viên không có ngày làm việc nào trong <?php echo mb_strtolower(payroll_period_label($period), 'UTF-8'); ?> (ngoài khoảng thời gian làm việc).</div>
  <?php else: ?>
  <?php echo form_open(current_url().'?period='.$period, array('id' => 'hoursForm')); ?>
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-2">
        <?php foreach ($days as $d): ?>
          <div class="d-flex align-items-center gap-2 py-1 <?php echo ! $d['in_range'] ? 'opacity-50' : ($d['is_weekend'] ? 'bg-light' : ''); ?>" style="border-radius:6px;">
            <div style="width:100px;" class="text-muted small">Ngày <?php echo $d['day']; ?> (<?php echo $d['weekday_label']; ?>)</div>
            <?php if ( ! $d['in_range']): ?>
              <span class="text-muted small fst-italic">Ngoài thời gian làm việc</span>
            <?php elseif ($is_hourly): ?>
              <input type="number" step="any" min="0" max="24" name="hours_<?php echo $d['day']; ?>" class="form-control form-control-sm hours-input" style="width:90px;" value="<?php echo htmlspecialchars((string) $d['hours']); ?>" placeholder="0">
              <span class="text-muted small">giờ</span>
            <?php else: ?>
              <select name="off_<?php echo $d['day']; ?>" class="form-select form-select-sm off-input" style="width:150px;">
                <option value="" <?php echo $d['off_value'] == 0 ? 'selected' : ''; ?>>Đi làm</option>
                <option value="1" <?php echo $d['off_value'] == 1 ? 'selected' : ''; ?>>Nghỉ cả ngày</option>
                <option value="0.5" <?php echo $d['off_value'] == 0.5 ? 'selected' : ''; ?>>Nghỉ nửa ngày</option>
              </select>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <?php if ($is_hourly): ?>
        <div class="fw-semibold">Tổng: <span id="totalHours"><?php echo rtrim(rtrim(number_format($total_hours, 2, '.', ''), '0'), '.'); ?></span> giờ</div>
      <?php else: ?>
        <div class="fw-semibold">Tổng: <span id="totalOff"><?php echo rtrim(rtrim(number_format($absence_days, 2, '.', ''), '0'), '.'); ?></span> ngày nghỉ</div>
      <?php endif; ?>
      <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Lưu</button>
    </div>
  <?php echo form_close(); ?>
  <?php endif; ?>
</div>

<script>
(function(){
  var hoursInputs = document.querySelectorAll('.hours-input');
  var totalHoursEl = document.getElementById('totalHours');
  if (totalHoursEl){
    function recalcHours(){
      var sum = 0;
      hoursInputs.forEach(function(i){ sum += parseFloat(i.value) || 0; });
      totalHoursEl.textContent = (Math.round(sum * 100) / 100).toString();
    }
    hoursInputs.forEach(function(i){ i.addEventListener('input', recalcHours); });
  }

  var offInputs = document.querySelectorAll('.off-input');
  var totalOffEl = document.getElementById('totalOff');
  if (totalOffEl){
    function recalcOff(){
      var sum = 0;
      offInputs.forEach(function(i){ sum += parseFloat(i.value) || 0; });
      totalOffEl.textContent = (Math.round(sum * 100) / 100).toString();
    }
    offInputs.forEach(function(i){ i.addEventListener('change', recalcOff); });
  }
})();
</script>
