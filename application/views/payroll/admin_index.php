<?php
  $prev_period = date('Y-m', strtotime($period.'-01 -1 month'));
  $next_period = date('Y-m', strtotime($period.'-01 +1 month'));

  $filter_qs = array();
  if ($keyword) $filter_qs['q'] = $keyword;
  if ($role_filter) $filter_qs['role'] = $role_filter;
  if ($salary_type_filter) $filter_qs['salary_type'] = $salary_type_filter;
  if ($paid_status_filter) $filter_qs['paid_status'] = $paid_status_filter;
  $has_filter = ! empty($filter_qs);
  $filter_qs_str = $filter_qs ? '&'.http_build_query($filter_qs) : '';
?>
<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-cash-coin"></i> Quản lý lương</h4>
    <div class="d-flex align-items-center gap-2">
      <a href="<?php echo site_url('me/payroll/admin').'?period='.$prev_period.$filter_qs_str; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
      <?php echo form_open('me/payroll/admin', array('method' => 'get', 'class' => 'd-flex gap-2')); ?>
        <input type="month" name="period" value="<?php echo $period; ?>" class="form-control form-control-sm" onchange="this.form.submit()">
      <?php echo form_close(); ?>
      <a href="<?php echo site_url('me/payroll/admin').'?period='.$next_period.$filter_qs_str; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <?php echo form_open('me/payroll/admin', array('method' => 'get', 'class' => 'row g-2 mb-3')); ?>
    <input type="hidden" name="period" value="<?php echo $period; ?>">
    <div class="col-auto">
      <input type="text" name="q" class="form-control" placeholder="Tìm theo tên đăng nhập hoặc họ tên..." value="<?php echo htmlspecialchars($keyword); ?>" style="min-width:220px;">
    </div>
    <div class="col-auto">
      <select name="role" class="form-select" onchange="this.form.submit()">
        <option value="">Tất cả vai trò</option>
        <?php foreach (array('STAFF','BARISTA','CASHIER','ADMIN','STOCKTAKER') as $r): ?>
          <option value="<?php echo $r; ?>" <?php echo $role_filter === $r ? 'selected' : ''; ?>><?php echo role_label($r); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="salary_type" class="form-select" onchange="this.form.submit()">
        <option value="">Tất cả loại lương</option>
        <option value="FIXED" <?php echo $salary_type_filter === 'FIXED' ? 'selected' : ''; ?>>Cố định</option>
        <option value="HOURLY" <?php echo $salary_type_filter === 'HOURLY' ? 'selected' : ''; ?>>Theo giờ</option>
      </select>
    </div>
    <div class="col-auto">
      <select name="paid_status" class="form-select" onchange="this.form.submit()">
        <option value="">Tất cả trạng thái</option>
        <option value="UNPAID" <?php echo $paid_status_filter === 'UNPAID' ? 'selected' : ''; ?>>Chưa chi</option>
        <option value="PAID" <?php echo $paid_status_filter === 'PAID' ? 'selected' : ''; ?>>Đã chi</option>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Tìm</button>
    </div>
    <?php if ($has_filter): ?>
    <div class="col-auto">
      <a href="<?php echo site_url('me/payroll/admin').'?period='.$period; ?>" class="btn btn-outline-secondary">Xoá lọc</a>
    </div>
    <?php endif; ?>
  <?php echo form_close(); ?>

  <h6 class="text-muted mb-2"><?php echo payroll_period_label($period); ?></h6>

  <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <span class="fw-bold"><i class="bi bi-cash-stack"></i> Tổng lương cần trả / Tổng lương (<?php echo $unpaid_count; ?>/<?php echo count($rows); ?> nhân viên chưa chi)</span>
    <span class="fs-4 fw-bold"><?php echo money_format_vnd($total_unpaid); ?> / <?php echo money_format_vnd($total_net); ?></span>
  </div>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded align-middle">
      <thead class="table-light">
        <tr>
          <th>STT</th>
          <th>Nhân viên</th>
          <th>Vai trò</th>
          <th>Loại lương</th>
          <th class="text-end">Mức lương</th>
          <th class="text-end">Lương tháng</th>
          <th class="text-end">Đã ứng</th>
          <th class="text-end">Còn lại</th>
          <th>Trạng thái</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php $stt = 1; foreach ($rows as $r): $u = $r['user']; $s = $r['salary']; ?>
        <tr>
          <td><?php echo $stt++; ?></td>
          <td>
            <?php echo htmlspecialchars($u['fullname']); ?>
            <?php if ($u['status'] === 'INACTIVE'): ?><span class="badge bg-secondary">Đã nghỉ việc</span><?php endif; ?>
          </td>
          <td><?php echo role_label($u['role']); ?></td>
          <td><?php echo $s['salary_type'] === 'HOURLY' ? 'Theo giờ' : 'Cố định'; ?></td>
          <td class="text-end text-nowrap">
            <?php echo $s['salary_type'] === 'HOURLY' ? money_format_vnd($s['hourly_rate']).'/giờ' : money_format_vnd($s['fixed_salary']).'/tháng'; ?>
          </td>
          <td class="text-end"><?php echo money_format_vnd($s['gross_salary']); ?></td>
          <td class="text-end"><?php echo money_format_vnd($s['advance_amount']); ?></td>
          <td class="text-end fw-semibold"><?php echo money_format_vnd($s['net_salary']); ?></td>
          <td>
            <?php if ($s['paid_status'] === 'PAID'): ?>
              <span class="badge bg-success">Đã chi</span>
            <?php else: ?>
              <span class="badge bg-secondary">Chưa chi</span>
            <?php endif; ?>
          </td>
          <td class="text-nowrap">
            <a href="<?php echo site_url('me/payroll/hours/'.$u['id']).'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary">Giờ làm</a>
            <a href="<?php echo site_url('me/payroll/record/'.$u['id']).'?period='.$period; ?>" class="btn btn-sm btn-outline-primary">Dữ liệu tháng</a>
            <a href="<?php echo site_url('me/payroll/settings/'.$u['id']); ?>" class="btn btn-sm btn-outline-secondary">Cấu hình</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">Chưa có nhân viên nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
