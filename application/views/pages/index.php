<?php
  // Quản trị → Trang nội dung / Tin tức: danh sách. Thẻ trên điện thoại, bảng trên máy tính.
  // Biến: $pages, $type (PAGE | NEWS), $base, $labels.
  $is_news = $type === 'NEWS';
  $dt = function ($v) { return $v ? date('d/m/Y H:i', strtotime($v)) : '—'; };
  $state = function ($p) {
    if ( ! (int) $p['status']) return '<span class="pb-state pb-state-off">Đang tắt</span>';
    if (Page_model::is_scheduled($p)) return '<span class="pb-state pb-state-wait" data-bs-toggle="tooltip" title="Tự hiện cho khách lúc '.date('H:i d/m/Y', strtotime($p['published_at'])).'"><i class="bi bi-clock"></i> Hẹn giờ</span>';
    return '<span class="pb-state pb-state-on">Đang hiện</span>';
  };
  $link = function ($p) { return '/'.Page_model::public_path($p).'.html'; };
  $views = function ($p) {
    $n = number_format((int) $p['view_count'], 0, ',', '.');
    $title = $p['last_viewed_at'] ? 'Xem gần nhất: '.date('d/m/Y H:i', strtotime($p['last_viewed_at'])) : 'Chưa có lượt xem';
    return '<span class="fw-semibold" data-bs-toggle="tooltip" title="'.$title.'"><i class="bi bi-eye text-muted"></i> '.$n.'</span>';
  };
  $pin = function ($p) use ($is_news) {
    return $is_news && (int) $p['is_pinned'] ? '<i class="bi bi-pin-angle-fill text-danger me-1" data-bs-toggle="tooltip" title="Đang ghim lên đầu"></i>' : '';
  };
  $actions = function ($p) use ($base, $labels) { return $this->load->view('pages/_actions', array('p' => $p, 'base' => $base, 'labels' => $labels), TRUE); };
?>
<div class="container-xl py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi <?php echo $labels['icon']; ?>"></i> <?php echo $labels['title']; ?></h4>
      <div class="text-muted small">
        <?php if ($is_news): ?>
          Tin mới / cập nhật của quán — hiện ở trang chủ (ngay dưới banner) và trang <a href="<?php echo site_url('tin-tuc'); ?>" target="_blank" rel="noopener">/tin-tuc.html</a>.
        <?php else: ?>
          Nội dung cho website: Giới thiệu, Khuyến mãi... Khách mở theo link <code>/page/&lt;mã trang&gt;.html</code>.
        <?php endif; ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/pages/images'); ?>" class="btn btn-outline-secondary"><i class="bi bi-images"></i> Thư viện ảnh</a>
      <a href="<?php echo site_url($base.'/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> <?php echo $labels['add']; ?></a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <?php if (empty($pages)): ?>
    <div class="orders-card text-center text-muted py-5">
      <i class="bi <?php echo $labels['icon']; ?> fs-1 d-block mb-2 opacity-50"></i>
      <?php if ($is_news): ?>
        Chưa có tin nào. Bấm <b>Thêm tin</b>, ví dụ "Khai trương khu vui chơi mới" hoặc "Lịch nghỉ lễ".
      <?php else: ?>
        Chưa có trang nào. Bấm <b>Thêm trang</b>, ví dụ trang "Giới thiệu" với mã <code>gioi-thieu</code>.
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="orders-card">
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table">
        <thead><tr>
          <th>Tiêu đề</th>
          <?php if ($is_news): ?><th>Ngày đăng</th><?php else: ?><th>Link</th><?php endif; ?>
          <th>Trạng thái</th><th class="text-end">Lượt xem</th><th>Tạo</th><th>Sửa gần nhất</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
          <tr>
            <td>
              <?php echo $pin($p); ?><a href="<?php echo site_url($base.'/'.$p['id'].'/edit'); ?>" class="fw-semibold text-body text-decoration-none"><?php echo htmlspecialchars($p['title']); ?></a>
              <?php if ($is_news): ?><div class="small"><code class="user-select-all"><?php echo htmlspecialchars($link($p)); ?></code></div><?php endif; ?>
            </td>
            <?php if ($is_news): ?>
              <td class="small text-nowrap"><?php echo $dt($p['published_at']); ?></td>
            <?php else: ?>
              <td class="small"><code class="user-select-all"><?php echo htmlspecialchars($link($p)); ?></code></td>
            <?php endif; ?>
            <td><?php echo $state($p); ?></td>
            <td class="text-end text-nowrap"><?php echo $views($p); ?></td>
            <td class="small text-nowrap"><?php echo $dt($p['created_at']); ?><div class="text-muted"><?php echo htmlspecialchars($p['created_by_name'] ?: '—'); ?></div></td>
            <td class="small text-nowrap"><?php echo $dt($p['updated_at']); ?><div class="text-muted"><?php echo htmlspecialchars($p['updated_by_name'] ?: '—'); ?></div></td>
            <td class="text-end text-nowrap"><?php echo $actions($p); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-md-none orders-list">
      <?php foreach ($pages as $p): ?>
        <div class="orders-item">
          <a href="<?php echo site_url($base.'/'.$p['id'].'/edit'); ?>" class="orders-item-main">
            <div class="fw-semibold"><?php echo $pin($p).htmlspecialchars($p['title']); ?></div>
            <div class="small"><code><?php echo htmlspecialchars($link($p)); ?></code></div>
            <div class="small text-muted">
              <?php echo $views($p); ?> · <?php echo $is_news ? 'Đăng '.$dt($p['published_at']) : 'Sửa '.$dt($p['updated_at']).' · '.htmlspecialchars($p['updated_by_name'] ?: '—'); ?>
            </div>
            <div class="mt-1"><?php echo $state($p); ?></div>
          </a>
          <div class="text-nowrap"><?php echo $actions($p); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
