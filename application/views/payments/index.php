<div class="container-fluid py-3 py-md-4">
  <h4 class="fw-bold mb-3">Lịch sử thanh toán</h4>
  <?php echo form_open('me/payments', array('method' => 'get', 'class' => 'row g-2 mb-3')); ?>
    <div class="col-auto"><input type="date" name="from" value="<?php echo $from; ?>" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to" value="<?php echo $to; ?>" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-brand">Lọc</button></div>
  <?php echo form_close(); ?>

  <div class="table-responsive">
    <table class="table bg-white shadow-sm rounded">
      <thead class="table-light"><tr><th>Phương thức</th><th class="text-end">Số giao dịch</th><th class="text-end">Tổng tiền</th></tr></thead>
      <tbody>
      <?php $grand = 0; foreach ($summary as $s): $grand += $s['total_amount']; ?>
        <tr>
          <td><?php echo $s['payment_method']; ?></td>
          <td class="text-end"><?php echo $s['total_count']; ?></td>
          <td class="text-end"><?php echo money_format_vnd($s['total_amount']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($summary)): ?>
        <tr><td colspan="3" class="text-center text-muted py-4">Không có giao dịch.</td></tr>
      <?php endif; ?>
      </tbody>
      <?php if ( ! empty($summary)): ?>
      <tfoot><tr class="fw-bold"><td>Tổng</td><td></td><td class="text-end"><?php echo money_format_vnd($grand); ?></td></tr></tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
