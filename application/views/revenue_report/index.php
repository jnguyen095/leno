<?php
  $prev_period = date('Y-m', strtotime($period.'-01 -1 month'));
  $next_period = date('Y-m', strtotime($period.'-01 +1 month'));
?>
<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart"></i> Báo cáo doanh thu</h4>
    <div class="d-flex align-items-center gap-2">
      <a href="<?php echo site_url('me/reports').'?period='.$prev_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
      <?php echo form_open('me/reports', array('method' => 'get', 'class' => 'd-flex gap-2')); ?>
        <input type="month" name="period" value="<?php echo $period; ?>" class="form-control form-control-sm" onchange="this.form.submit()">
      <?php echo form_close(); ?>
      <a href="<?php echo site_url('me/reports').'?period='.$next_period; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
      <a href="<?php echo site_url('me/reports/entry').'?period='.$period; ?>" class="btn btn-brand btn-sm"><i class="bi bi-pencil-square"></i> Nhập doanh thu</a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-1">
    <div class="col-12 col-md-4 col-xs-12">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <i class="bi bi-cash-stack text-success fs-2"></i>
          <div class="fs-5 fw-bold mt-1"><?php echo money_format_vnd($total); ?></div>
          <div class="text-muted small">Tổng doanh thu <?php echo mb_strtolower(payroll_period_label($period), 'UTF-8'); ?></div>
        </div>
      </div>
    </div>
    <?php foreach ($breakdown as $b): ?>
    <div class="col-6 col-md-2 col-xs-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <span class="d-inline-block rounded-circle mb-1" style="width:14px;height:14px;background:<?php echo revenue_category_color($b['category']); ?>;"></span>
          <div class="fs-6 fw-bold"><?php echo money_format_vnd($b['revenue']); ?></div>
          <div class="text-muted small"><?php echo revenue_category_label($b['category']); ?> (<?php echo rtrim(rtrim(number_format($b['percent'], 1, '.', ''), '0'), '.'); ?>%)</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Doanh thu 12 tháng gần nhất</h6>
          <canvas id="chartTrend" height="260"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Tỷ lệ theo danh mục — <?php echo payroll_period_label($period); ?></h6>
          <?php if ($total > 0): ?>
            <canvas id="chartPercent" height="260"></canvas>
          <?php else: ?>
            <p class="text-muted small mb-0">Chưa có doanh thu nào được nhập cho tháng này.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-3">
    <div class="card-body">
      <h6 class="fw-bold mb-3">Chi tiết <?php echo mb_strtolower(payroll_period_label($period), 'UTF-8'); ?></h6>
      <table class="table table-sm mb-0">
        <thead class="text-muted small"><tr><th>Danh mục</th><th class="text-end">Doanh thu</th><th class="text-end">Tỷ lệ</th><th class="text-end">So với tháng trước</th></tr></thead>
        <tbody>
        <?php foreach ($breakdown as $b): ?>
          <tr>
            <td>
              <span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:<?php echo revenue_category_color($b['category']); ?>;"></span>
              <?php echo revenue_category_label($b['category']); ?>
            </td>
            <td class="text-end"><?php echo money_format_vnd($b['revenue']); ?></td>
            <td class="text-end"><?php echo rtrim(rtrim(number_format($b['percent'], 1, '.', ''), '0'), '.'); ?>%</td>
            <td class="text-end fw-semibold <?php echo $b['change_label']['class']; ?>"><?php echo $b['change_label']['text']; ?></td>
          </tr>
        <?php endforeach; ?>
        <tr class="fw-bold border-top">
          <td>Tổng</td>
          <td class="text-end"><?php echo money_format_vnd($total); ?></td>
          <td class="text-end">100%</td>
          <td class="text-end <?php echo $total_change_label['class']; ?>"><?php echo $total_change_label['text']; ?></td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){
  var periods = <?php echo json_encode($periods); ?>;
  var trend = <?php echo json_encode($trend, JSON_UNESCAPED_UNICODE); ?>;
  var categories = <?php echo json_encode(Monthly_revenue_model::CATEGORIES); ?>;
  var categoryLabels = {
    <?php foreach (Monthly_revenue_model::CATEGORIES as $c): ?>
    '<?php echo $c; ?>': '<?php echo revenue_category_label($c); ?>',
    <?php endforeach; ?>
  };
  var categoryColors = {
    <?php foreach (Monthly_revenue_model::CATEGORIES as $c): ?>
    '<?php echo $c; ?>': '<?php echo revenue_category_color($c); ?>',
    <?php endforeach; ?>
  };

  var labels = periods.map(function(p){ var parts = p.split('-'); return parts[1]+'/'+parts[0]; });
  var datasets = categories.map(function(c){
    return {
      label: categoryLabels[c],
      backgroundColor: categoryColors[c],
      data: periods.map(function(p){ return trend[p].by_category[c] || 0; }),
      stack: 'total',
    };
  });

  new Chart(document.getElementById('chartTrend'), {
    type: 'bar',
    data: { labels: labels, datasets: datasets },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
    }
  });

  var percentCanvas = document.getElementById('chartPercent');
  if (percentCanvas){
    var breakdown = <?php echo json_encode($breakdown); ?>;
    new Chart(percentCanvas, {
      type: 'doughnut',
      data: {
        labels: breakdown.map(function(b){ return categoryLabels[b.category]; }),
        datasets: [{
          data: breakdown.map(function(b){ return b.revenue; }),
          backgroundColor: breakdown.map(function(b){ return categoryColors[b.category]; }),
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
      }
    });
  }
})();
</script>
