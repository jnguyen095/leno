<?php
  // Thao tác một dòng Sổ quỹ. Tiền bán hàng: xem đơn. Phiếu nhập tay: sửa / xoá (chỉ ADMIN). Biến: $r, $is_admin, $qs.
?>
<?php if ($r['src'] === 'SALE'): ?>
  <a href="<?php echo site_url('me/orders/'.$r['order_id']); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Xem đơn <?php echo htmlspecialchars($r['code']); ?>"><i class="bi bi-receipt"></i></a>
<?php elseif ($is_admin): ?>
  <button type="button" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sửa phiếu"
          data-entry="<?php echo htmlspecialchars(json_encode(array(
            'id' => (int) $r['id'], 'code' => $r['code'], 'type' => $r['type'], 'dt' => $r['dt'], 'payment_method' => $r['payment_method'],
            'category_id' => (int) $r['category_id'], 'counterparty' => $r['counterparty'], 'handled_by' => (int) $r['handled_by'],
            'amount' => (float) $r['amount'], 'note' => $r['note'],
          ), JSON_UNESCAPED_UNICODE)); ?>"><i class="bi bi-pencil"></i></button>
  <?php echo form_open('me/cashbook/'.$r['id'].'/delete', array('class' => 'd-inline',
        'data-confirm' => html_escape('Xoá phiếu '.$r['code'].'?'),
        'data-confirm-message' => html_escape(($r['type'] === 'IN' ? 'Thu ' : 'Chi ').number_format((float) $r['amount'], 0, ',', '.').'đ — không khôi phục được.'))); ?>
    <input type="hidden" name="back_qs" value="<?php echo htmlspecialchars($qs); ?>">
    <button class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Xoá phiếu"><i class="bi bi-trash"></i></button>
  <?php echo form_close(); ?>
<?php endif; ?>
