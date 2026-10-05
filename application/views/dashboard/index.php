<div class="container-fluid py-3 py-md-4">
  <h4 class="fw-bold mb-3">Xin chào, <?php echo htmlspecialchars($current_user['fullname']); ?> 👋</h4>

  <div class="row g-3">
    <div class="col-6 col-md-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <i class="bi bi-boxes text-brand fs-2"></i>
          <div class="fs-4 fw-bold mt-1"><?php echo $total_items; ?></div>
          <div class="text-muted small">Sản phẩm trong kho</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <i class="bi bi-exclamation-triangle text-danger fs-2"></i>
          <div class="fs-4 fw-bold mt-1"><?php echo $total_low_stock; ?></div>
          <div class="text-muted small">Sản phẩm sắp hết hàng</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body text-center">
          <i class="bi bi-tags text-primary fs-2"></i>
          <div class="fs-4 fw-bold mt-1"><?php echo $total_categories; ?></div>
          <div class="text-muted small">Danh mục kho</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-tags"></i> Tồn kho theo danh mục</h6>
          <?php if (empty($category_summary)): ?>
            <p class="text-muted small mb-0">Chưa có sản phẩm kho nào.</p>
          <?php else: ?>
            <?php foreach ($category_summary as $c): $has_low = $c['low'] > 0; ?>
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><?php echo htmlspecialchars($c['category_name'] ?: 'Chưa phân loại'); ?></span>
                <span class="fw-semibold <?php echo $has_low ? 'text-danger' : 'text-success'; ?>">
                  <?php echo (int) $c['low']; ?>/<?php echo (int) $c['total']; ?>
                  <?php if ($has_low): ?><i class="bi bi-exclamation-triangle-fill ms-1"></i><?php endif; ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> Sắp hết hàng</h6>
            <a href="<?php echo site_url('me/inventory/items?stock_status=LOW'); ?>" class="small">Xem tất cả</a>
          </div>
          <?php if (empty($top_low_stock)): ?>
            <p class="text-muted small mb-0">Không có sản phẩm nào sắp hết hàng.</p>
          <?php else: ?>
            <table class="table table-sm mb-0">
              <tbody>
              <?php foreach ($top_low_stock as $it): ?>
                <tr>
                  <td>
                    <div><?php echo htmlspecialchars($it['name']); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($it['category_name']); ?></div>
                  </td>
                  <td class="text-end text-danger fw-semibold text-nowrap">
                    <?php echo rtrim(rtrim(number_format($it['qty_on_hand'], 2, '.', ''), '0'), '.'); ?> / <?php echo rtrim(rtrim(number_format($it['low_stock_threshold'], 2, '.', ''), '0'), '.'); ?> <?php echo htmlspecialchars($it['unit_name']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history"></i> Lịch sử nhập/xuất/kiểm kho gần đây</h6>
            <a href="<?php echo site_url('me/stock/history'); ?>" class="small">Xem tất cả</a>
          </div>
          <?php if (empty($recent_batches)): ?>
            <p class="text-muted small mb-0">Chưa có giao dịch nào.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="text-muted small"><tr><th>Thời gian</th><th>Loại</th><th>Số SP</th><th class="text-end">Tổng SL</th><th>Người thực hiện</th></tr></thead>
                <tbody>
                <?php foreach ($recent_batches as $b): ?>
                  <tr>
                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></td>
                    <td>
                      <?php if ($b['type'] === 'IN'): ?>
                        <span class="badge bg-success">Nhập</span>
                      <?php elseif ($b['type'] === 'OUT'): ?>
                        <span class="badge bg-danger">Xuất</span>
                      <?php else: ?>
                        <span class="badge bg-primary">Kiểm kho</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo (int) $b['item_count']; ?></td>
                    <td class="text-end"><?php echo ($b['type'] === 'ADJUST' && $b['total_qty'] > 0 ? '+' : '').rtrim(rtrim(number_format($b['total_qty'], 2, '.', ''), '0'), '.'); ?></td>
                    <td><?php echo htmlspecialchars((string) $b['created_by_name']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
