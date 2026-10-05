<?php
  $prev_period = date('Y-m', strtotime($period.'-01 -1 month'));
  $next_period = date('Y-m', strtotime($period.'-01 +1 month'));
?>
<div class="container py-3 py-md-4" style="max-width:640px;">
  <h4 class="fw-bold mb-3"><i class="bi bi-cash-coin"></i> Lương của tôi</h4>

  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
    <a href="<?php echo site_url('me/payroll').'?period='.$prev_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
    <?php echo form_open('me/payroll', array('method' => 'get', 'class' => 'd-flex gap-2')); ?>
      <input type="month" name="period" value="<?php echo $period; ?>" class="form-control form-control-sm" onchange="this.form.submit()">
    <?php echo form_close(); ?>
    <a href="<?php echo site_url('me/payroll').'?period='.$next_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h5 class="fw-bold mb-0"><?php echo payroll_period_label($period); ?></h5>
        <?php if ($salary['paid_status'] === 'PAID'): ?>
          <span class="badge bg-success">Đã chi lương</span>
        <?php else: ?>
          <span class="badge bg-secondary">Chưa chi lương</span>
        <?php endif; ?>
      </div>

      <?php if ($salary['salary_type'] === 'HOURLY'): ?>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted">Loại lương</td><td class="text-end">Theo giờ</td></tr>
          <tr><td class="text-muted">Đơn giá/giờ</td><td class="text-end"><?php echo money_format_vnd($salary['hourly_rate']); ?></td></tr>
          <tr><td class="text-muted">Tổng giờ làm</td><td class="text-end"><?php echo rtrim(rtrim(number_format($salary['total_hours'], 2, '.', ''), '0'), '.'); ?> giờ</td></tr>
          <tr><td class="fw-semibold">Lương tháng</td><td class="text-end fw-semibold"><?php echo money_format_vnd($salary['gross_salary']); ?></td></tr>
        </table>
        <a href="<?php echo site_url('me/payroll/detail').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-list-check"></i> Xem chi tiết giờ làm</a>
      <?php else: ?>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted">Loại lương</td><td class="text-end">Cố định</td></tr>
          <tr><td class="text-muted">Lương cố định</td><td class="text-end"><?php echo money_format_vnd($salary['fixed_salary']); ?></td></tr>
          <tr><td class="text-muted">Số ngày nghỉ</td><td class="text-end"><?php echo rtrim(rtrim(number_format($salary['absence_days'], 2, '.', ''), '0'), '.'); ?> ngày</td></tr>
          <?php if ($salary['absence_days'] > 0): ?>
          <tr><td class="text-muted">Trừ (<?php echo money_format_vnd($salary['daily_rate']); ?>/ngày)</td><td class="text-end text-danger">-<?php echo money_format_vnd($salary['daily_rate'] * $salary['absence_days']); ?></td></tr>
          <?php endif; ?>
          <tr><td class="fw-semibold">Lương tháng</td><td class="text-end fw-semibold"><?php echo money_format_vnd($salary['gross_salary']); ?></td></tr>
        </table>
        <a href="<?php echo site_url('me/payroll/detail').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-list-check"></i> Xem chi tiết ngày nghỉ</a>
      <?php endif; ?>

      <hr>
      <table class="table table-sm mb-0">
        <?php if ($salary['bonus_total'] > 0): ?>
        <tr><td class="text-muted">Thưởng</td><td class="text-end text-success">+<?php echo money_format_vnd($salary['bonus_total']); ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted">Đã ứng lương</td><td class="text-end"><?php echo money_format_vnd($salary['advance_amount']); ?></td></tr>
        <tr><td class="fw-bold">Còn lại</td><td class="text-end fw-bold fs-5 text-brand"><?php echo money_format_vnd($salary['net_salary']); ?></td></tr>
      </table>
      <?php if ($salary['note']): ?>
        <div class="small text-muted mt-2"><i class="bi bi-chat-left-text"></i> <?php echo htmlspecialchars($salary['note']); ?></div>
      <?php endif; ?>
      <?php if ( ! empty($bonuses)): ?>
        <hr>
        <div class="small fw-semibold mb-1"><i class="bi bi-gift"></i> Chi tiết thưởng</div>
        <?php foreach ($bonuses as $b): ?>
          <div class="small text-muted d-flex justify-content-between">
            <span><?php echo $b['note'] ? htmlspecialchars($b['note']) : 'Thưởng'; ?></span>
            <span class="text-success">+<?php echo money_format_vnd($b['amount']); ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-bank"></i> Thông tin ngân hàng</h6>
        <a href="<?php echo site_url('me/payroll/bank-info'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
      </div>
      <?php if ($bank_info['bank_name'] || $bank_info['bank_account_number']): ?>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted" style="width:40%;">Ngân hàng</td><td><?php echo htmlspecialchars((string) $bank_info['bank_name']); ?></td></tr>
          <tr><td class="text-muted">Số tài khoản</td><td><?php echo htmlspecialchars((string) $bank_info['bank_account_number']); ?></td></tr>
          <tr><td class="text-muted">Chủ tài khoản</td><td><?php echo htmlspecialchars((string) $bank_info['bank_account_name']); ?></td></tr>
        </table>
      <?php else: ?>
        <p class="text-muted small mb-0">Chưa có thông tin ngân hàng. Bấm "Sửa" để cập nhật.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
