<?php
  // Quản trị → Trang nội dung: danh sách trang website. Thẻ trên điện thoại, bảng trên máy tính.
  $dt = function ($v) { return $v ? date('d/m/Y H:i', strtotime($v)) : '—'; };
  $state = function ($p) {
    return (int) $p['status'] ? '<span class="pb-state pb-state-on">Đang bật</span>' : '<span class="pb-state pb-state-off">Đang tắt</span>';
  };
  $link = function ($p) { return '/page/'.$p['code'].'.html'; };
?>
<div class="container-xl py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-richtext"></i> Trang nội dung</h4>
      <div class="text-muted small">Nội dung cho website: Giới thiệu, Khuyến mãi... Khách mở theo link <code>/page/&lt;mã trang&gt;.html</code>.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('me/pages/images'); ?>" class="btn btn-outline-secondary"><i class="bi bi-images"></i> Thư viện ảnh</a>
      <a href="<?php echo site_url('me/pages/create'); ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Thêm trang</a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <?php if (empty($pages)): ?>
    <div class="orders-card text-center text-muted py-5">
      <i class="bi bi-file-earmark-richtext fs-1 d-block mb-2 opacity-50"></i>
      Chưa có trang nào. Bấm <b>Thêm trang</b>, ví dụ trang "Giới thiệu" với mã <code>gioi-thieu</code>.
    </div>
  <?php else: ?>
  <div class="orders-card">
    <div class="table-responsive d-none d-md-block">
      <table class="table table-hover align-middle mb-0 orders-table">
        <thead><tr><th>Tiêu đề</th><th>Link</th><th>Trạng thái</th><th>Tạo</th><th>Sửa gần nhất</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
          <tr>
            <td><a href="<?php echo site_url('me/pages/'.$p['id'].'/edit'); ?>" class="fw-semibold text-body text-decoration-none"><?php echo htmlspecialchars($p['title']); ?></a></td>
            <td class="small"><code class="user-select-all"><?php echo htmlspecialchars($link($p)); ?></code></td>
            <td><?php echo $state($p); ?></td>
            <td class="small text-nowrap"><?php echo $dt($p['created_at']); ?><div class="text-muted"><?php echo htmlspecialchars($p['created_by_name'] ?: '—'); ?></div></td>
            <td class="small text-nowrap"><?php echo $dt($p['updated_at']); ?><div class="text-muted"><?php echo htmlspecialchars($p['updated_by_name'] ?: '—'); ?></div></td>
            <td class="text-end text-nowrap"><?php $this->load->view('pages/_actions', array('p' => $p)); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-md-none orders-list">
      <?php foreach ($pages as $p): ?>
        <div class="orders-item">
          <a href="<?php echo site_url('me/pages/'.$p['id'].'/edit'); ?>" class="orders-item-main">
            <div class="fw-semibold"><?php echo htmlspecialchars($p['title']); ?></div>
            <div class="small"><code><?php echo htmlspecialchars($link($p)); ?></code></div>
            <div class="small text-muted">Sửa <?php echo $dt($p['updated_at']); ?> · <?php echo htmlspecialchars($p['updated_by_name'] ?: '—'); ?></div>
            <div class="mt-1"><?php echo $state($p); ?></div>
          </a>
          <div class="text-nowrap"><?php $this->load->view('pages/_actions', array('p' => $p)); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
