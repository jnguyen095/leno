<?php
  $prev_period = date('Y-m', strtotime($period.'-01 -1 month'));
  $next_period = date('Y-m', strtotime($period.'-01 +1 month'));
?>
<div class="container py-3 py-md-4" style="max-width:500px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-calendar-week"></i> Chi tiết <?php echo $settings['salary_type'] === 'HOURLY' ? 'giờ làm' : 'ngày nghỉ'; ?></h4>
    <a href="<?php echo site_url('me/payroll').'?period='.$period; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
  </div>

  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
    <a href="<?php echo site_url('me/payroll/detail').'?period='.$prev_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-semibold"><?php echo payroll_period_label($period); ?></span>
    <a href="<?php echo site_url('me/payroll/detail').'?period='.$next_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <?php if ($settings['salary_type'] === 'HOURLY'): ?>
        <?php if (empty($hour_entries)): ?>
          <p class="text-muted small mb-0">Chưa có ngày làm nào được ghi nhận trong tháng này.</p>
        <?php else: ?>
          <table class="table table-sm mb-0">
            <thead class="text-muted small"><tr><th>Ngày</th><th class="text-end">Giờ làm</th></tr></thead>
            <tbody>
            <?php foreach ($hour_entries as $e): ?>
              <tr>
                <td>Ngày <?php echo $e['day']; ?> (<?php echo $e['weekday_label']; ?>)</td>
                <td class="text-end"><?php echo rtrim(rtrim(number_format($e['hours'], 2, '.', ''), '0'), '.'); ?> giờ</td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="fw-bold border-top">
                <td>Tổng</td>
                <td class="text-end"><?php echo rtrim(rtrim(number_format($total_hours, 2, '.', ''), '0'), '.'); ?> giờ</td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      <?php else: ?>
        <?php if (empty($off_entries)): ?>
          <p class="text-muted small mb-0">Không có ngày nghỉ nào được ghi nhận trong tháng này.</p>
        <?php else: ?>
          <table class="table table-sm mb-0">
            <thead class="text-muted small"><tr><th>Ngày</th><th class="text-end">Loại nghỉ</th></tr></thead>
            <tbody>
            <?php foreach ($off_entries as $e): ?>
              <tr>
                <td>Ngày <?php echo $e['day']; ?> (<?php echo $e['weekday_label']; ?>)</td>
                <td class="text-end"><?php echo $e['fraction'] == 1 ? 'Cả ngày' : 'Nửa ngày'; ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="fw-bold border-top">
                <td>Tổng</td>
                <td class="text-end"><?php echo rtrim(rtrim(number_format($total_off, 2, '.', ''), '0'), '.'); ?> ngày</td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
