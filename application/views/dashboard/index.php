<?php
  // Tổng quan: bộ lọc thời gian | cột chính (~80%: chỉ số + 4 thẻ 2×2) | cột phải (~20%: hoạt động gần đây).
  // Biểu đồ vẽ bằng HTML/CSS (1 màu cho 1 chuỗi số liệu, thanh ≤24px bo 4px đầu, lưới mảnh), có chú thích khi rê chuột
  // và nút "Bảng" để xem số liệu dạng bảng.
  $money = function ($v) { return number_format(round((float) $v), 0, ',', '.'); };
  $short = function ($v) { $v = (float) $v; if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',').'tr'; if ($v >= 1000) return round($v / 1000).'k'; return (string) round($v); };
  $delta = function ($cur, $prev) { if ($prev === NULL) return NULL; if ($prev <= 0) return $cur > 0 ? 'new' : NULL; return ($cur - $prev) / $prev * 100; };
  $rev_delta = $prev ? $delta($kpis['revenue'], $prev['revenue']) : NULL;
  $ord_delta = $prev ? $delta($kpis['orders'], $prev['orders']) : NULL;
  $delta_html = function ($d) use ($prev_label) {
    if ($d === NULL) return '';
    if ($d === 'new') return '<span class="dash-delta up"><i class="bi bi-arrow-up-short"></i>mới · so với '.$prev_label.'</span>';
    $up = $d >= 0;
    return '<span class="dash-delta '.($up ? 'up' : 'down').'"><i class="bi bi-arrow-'.($up ? 'up' : 'down').'-short"></i>'.abs(round($d)).'% · so với '.$prev_label.'</span>';
  };
  // Biểu đồ doanh thu: thang chia tròn (0 / bước / 2 bước / ...).
  $max = max(array_merge(array(0), array_column($series, 'value')));
  $nice = function ($v) { if ($v <= 0) return 1; $p = pow(10, floor(log10($v))); foreach (array(1, 2, 2.5, 5, 10) as $m) if ($m * $p >= $v) return $m * $p; return 10 * $p; };
  $step = $nice($max / 4); $axis_max = $step * max(1, ceil($max / $step));
  $peak_i = $max > 0 ? array_search($max, array_column($series, 'value')) : -1;
  // Nhãn trục X: tối đa ~7 nhãn để không đè nhau (kể cả trên điện thoại); chú thích khi rê chuột vẫn đủ từng cột.
  $n = count($series); $label_every = $n > 10 ? (int) ceil($n / 7) : 1;
  $top_max = $top_products ? max(array_map(function ($p) { return (float) $p['qty']; }, $top_products)) : 0;
  $cat_total = array_sum(array_map(function ($c) { return (float) $c['revenue']; }, $by_category));
  $cat_max = $by_category ? max(array_map(function ($c) { return (float) $c['revenue']; }, $by_category)) : 0;
  $pm_total = array_sum($by_method);
  $range_text = $from === NULL ? 'Toàn bộ thời gian' : (substr($from, 0, 10) === substr($to, 0, 10) ? date('d/m/Y', strtotime($from)) : date('d/m', strtotime($from)).' – '.date('d/m/Y', strtotime($to)));
  $bucket_label = array('hour' => 'theo giờ', 'day' => 'theo ngày', 'month' => 'theo tháng')[$bucket];
?>
<div class="container-fluid py-3 py-md-4 dash-page">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0"><i class="bi bi-speedometer2"></i> Tổng quan</h4>
      <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo $range_text; ?> · doanh thu tính theo ngày thanh toán</div>
    </div>
    <nav class="orders-presets m-0" aria-label="Khoảng thời gian">
      <?php foreach ($ranges as $k => $label): ?>
        <a href="<?php echo site_url('me/dashboard').'?range='.$k; ?>" class="orders-chip <?php echo $range === $k ? 'active' : ''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="dash-grid <?php echo $show_activity ? '' : 'no-activity'; ?>">
    <div class="dash-main">
      <?php // ---------- Chỉ số chính ---------- ?>
      <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-cash-stack"></i> Doanh thu</div>
            <div class="dash-tile-value"><?php echo $money($kpis['revenue']); ?><small>đ</small></div><?php echo $delta_html($rev_delta); ?></div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-receipt"></i> Đơn đã thanh toán</div>
            <div class="dash-tile-value"><?php echo $kpis['orders']; ?></div><?php echo $delta_html($ord_delta); ?></div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-calculator"></i> Trung bình / đơn</div>
            <div class="dash-tile-value"><?php echo $money($kpis['avg']); ?><small>đ</small></div><span class="dash-sub"><?php echo $kpis['items']; ?> món đã bán</span></div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="dash-tile"><div class="dash-tile-label"><i class="bi bi-hourglass-split"></i> Đang phục vụ</div>
            <div class="dash-tile-value"><?php echo $open_now['count']; ?> <small>đơn</small></div><span class="dash-sub">chưa thu <?php echo $money($open_now['total']); ?>đ · ngay lúc này</span></div>
        </div>
      </div>

      <div class="dash-cards">
        <?php // ---------- Doanh thu theo thời gian (1 chuỗi -> 1 màu, không cần chú giải) ---------- ?>
        <section class="dash-card">
          <header class="dash-card-head">
            <div><h6>Doanh thu <?php echo $bucket_label; ?></h6><p><?php echo $range_text; ?></p></div>
            <button type="button" class="btn btn-sm btn-light dash-table-toggle" data-target="revTable" data-bs-toggle="tooltip" title="Xem dạng bảng"><i class="bi bi-table"></i></button>
          </header>
          <?php if ($max <= 0): ?>
            <div class="dash-empty"><i class="bi bi-bar-chart"></i> Chưa có doanh thu trong khoảng này.</div>
          <?php else: ?>
          <div class="dash-chart" role="img" aria-label="Biểu đồ doanh thu <?php echo $bucket_label; ?>, cao nhất <?php echo $money($max); ?> đồng">
            <div class="dash-chart-plot">
              <?php for ($t = $axis_max; $t >= 0; $t -= $step): ?>
                <div class="dash-grid-line" style="bottom:<?php echo $t / $axis_max * 100; ?>%"><span><?php echo $short($t); ?></span></div>
              <?php endfor; ?>
              <div class="dash-bars" style="--n:<?php echo $n; ?>">
                <?php foreach ($series as $i => $s): ?>
                  <div class="dash-bar-slot" data-tip="<?php echo htmlspecialchars('<b>'.$s['tip'].'</b><br>'.$money($s['value']).' đ · '.$s['orders'].' đơn'); ?>">
                    <?php if ($i === $peak_i): ?><span class="dash-peak"><?php echo $short($s['value']); ?></span><?php endif; ?>
                    <div class="dash-bar" style="height:<?php echo $s['value'] / $axis_max * 100; ?>%"></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="dash-xlabels" style="--n:<?php echo $n; ?>">
              <?php foreach ($series as $i => $s): ?><span><?php echo $i % $label_every === 0 ? $s['label'] : ''; ?></span><?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="dash-table d-none" id="revTable">
            <table class="table table-sm mb-0"><thead><tr><th>Thời gian</th><th class="text-end">Đơn</th><th class="text-end">Doanh thu</th></tr></thead><tbody>
              <?php foreach ($series as $s): if ( ! $s['value']) continue; ?><tr><td><?php echo $s['tip']; ?></td><td class="text-end"><?php echo $s['orders']; ?></td><td class="text-end"><?php echo $money($s['value']); ?></td></tr><?php endforeach; ?>
            </tbody></table>
          </div>
        </section>

        <?php // ---------- Món bán chạy (thanh ngang, 1 màu) ---------- ?>
        <section class="dash-card">
          <header class="dash-card-head">
            <div><h6>Món bán chạy</h6><p>Theo số lượng bán</p></div>
            <button type="button" class="btn btn-sm btn-light dash-table-toggle" data-target="topTable" data-bs-toggle="tooltip" title="Xem dạng bảng"><i class="bi bi-table"></i></button>
          </header>
          <?php if ( ! $top_products): ?>
            <div class="dash-empty"><i class="bi bi-cup-hot"></i> Chưa có món nào được bán.</div>
          <?php else: ?>
            <div class="dash-hbars">
              <?php foreach ($top_products as $i => $p): ?>
                <div class="dash-hbar" data-tip="<?php echo htmlspecialchars('<b>'.htmlspecialchars($p['name']).'</b><br>'.(int) $p['qty'].' phần · '.$money($p['revenue']).' đ'); ?>">
                  <span class="dash-rank"><?php echo $i + 1; ?></span>
                  <div class="dash-hbar-body">
                    <div class="dash-hbar-text"><span class="text-truncate"><?php echo htmlspecialchars($p['name']); ?></span><b><?php echo (int) $p['qty']; ?></b></div>
                    <div class="dash-hbar-track"><div class="dash-hbar-fill" style="width:<?php echo $top_max ? $p['qty'] / $top_max * 100 : 0; ?>%"></div></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="dash-table d-none" id="topTable">
            <table class="table table-sm mb-0"><thead><tr><th>#</th><th>Món</th><th class="text-end">SL</th><th class="text-end">Doanh thu</th></tr></thead><tbody>
              <?php foreach ($top_products as $i => $p): ?><tr><td><?php echo $i + 1; ?></td><td><?php echo htmlspecialchars($p['name']); ?></td><td class="text-end"><?php echo (int) $p['qty']; ?></td><td class="text-end"><?php echo $money($p['revenue']); ?></td></tr><?php endforeach; ?>
            </tbody></table>
          </div>
        </section>

        <?php // ---------- Doanh thu theo danh mục + hình thức thanh toán ---------- ?>
        <section class="dash-card">
          <header class="dash-card-head"><div><h6>Doanh thu theo danh mục</h6><p>Tỷ trọng trên tổng tiền món</p></div></header>
          <?php if ( ! $by_category): ?>
            <div class="dash-empty"><i class="bi bi-collection"></i> Chưa có dữ liệu.</div>
          <?php else: ?>
            <div class="dash-hbars">
              <?php foreach ($by_category as $c): $share = $cat_total ? $c['revenue'] / $cat_total * 100 : 0; ?>
                <div class="dash-hbar" data-tip="<?php echo htmlspecialchars('<b>'.htmlspecialchars($c['name']).'</b><br>'.$money($c['revenue']).' đ · '.(int) $c['qty'].' phần · '.round($share).'%'); ?>">
                  <div class="dash-hbar-body">
                    <div class="dash-hbar-text"><span class="text-truncate"><?php echo htmlspecialchars($c['name']); ?></span><b><?php echo round($share); ?>%</b></div>
                    <div class="dash-hbar-track"><div class="dash-hbar-fill" style="width:<?php echo $cat_max ? $c['revenue'] / $cat_max * 100 : 0; ?>%"></div></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php // Hình thức thanh toán: 2 nhóm -> 2 màu + chú giải có số tiền (không chỉ dựa vào màu). ?>
          <div class="dash-split">
            <div class="dash-split-title">Hình thức thanh toán</div>
            <div class="dash-split-bar">
              <?php $pi = 0; foreach ($by_method as $code => $amt): $pi++; if ($amt <= 0) continue; ?>
                <div class="dash-split-seg s<?php echo $pi; ?>" style="flex:<?php echo $amt; ?>" data-tip="<?php echo htmlspecialchars('<b>'.payment_method_label($code).'</b><br>'.$money($amt).' đ · '.round($amt / $pm_total * 100).'%'); ?>"></div>
              <?php endforeach; ?>
              <?php if ($pm_total <= 0): ?><div class="dash-split-seg empty" style="flex:1"></div><?php endif; ?>
            </div>
            <div class="dash-legend">
              <?php $pi = 0; foreach ($by_method as $code => $amt): $pi++; ?>
                <span><i class="dash-key s<?php echo $pi; ?>"></i><?php echo payment_method_label($code); ?> <b><?php echo $money($amt); ?>đ</b><?php echo $pm_total ? ' · '.round($amt / $pm_total * 100).'%' : ''; ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

        <?php // ---------- Bảng giá & khuyến mãi ---------- ?>
        <section class="dash-card">
          <header class="dash-card-head"><div><h6>Bảng giá & khuyến mãi</h6><p>Đang chọn ở Sơ đồ bàn · hiệu lực hôm nay</p></div>
            <a href="<?php echo site_url('me/tables'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Đổi ở Sơ đồ bàn"><i class="bi bi-grid-3x3-gap"></i></a></header>
          <div class="dash-offer <?php echo $shop_offers['price_book'] ? 'on' : ''; ?>">
            <i class="bi bi-tags"></i>
            <div><small>Bảng giá đang áp</small><b><?php echo $shop_offers['price_book'] ? htmlspecialchars($shop_offers['price_book']['name']) : 'Giá gốc'; ?></b></div>
          </div>
          <div class="dash-offer <?php echo $shop_offers['discount'] ? 'on disc' : ''; ?>">
            <i class="bi bi-percent"></i>
            <div><small>Khuyến mãi đang áp</small><b><?php echo $shop_offers['discount'] ? htmlspecialchars($shop_offers['discount']['name']) : 'Không áp dụng'; ?></b>
              <?php if ($shop_offers['discount']): ?><em><?php echo htmlspecialchars(Discount_model::describe($shop_offers['discount']['discount_type'], $shop_offers['discount']['value'], $shop_offers['discount']['max_discount'], $shop_offers['discount']['min_order'])); ?></em><?php endif; ?></div>
          </div>
          <div class="dash-offer-stats">
            <div><b><?php echo (int) $offer_usage['book_orders']; ?></b><span>đơn theo bảng giá</span></div>
            <div><b><?php echo (int) $offer_usage['discount_orders']; ?></b><span>đơn được khuyến mãi</span></div>
            <div><b><?php echo $money($kpis['discount'] + $kpis['book_saving']); ?>đ</b><span>khách tiết kiệm</span></div>
          </div>
          <div class="dash-offer-list">
            <div class="small fw-semibold text-muted mb-1">Hiệu lực hôm nay</div>
            <?php if ( ! $price_books && ! $discounts): ?><div class="small text-muted">Không có bảng giá / khuyến mãi nào hôm nay.</div><?php endif; ?>
            <?php foreach ($price_books as $pb): ?><span class="dash-pill"><i class="bi bi-tags"></i> <?php echo htmlspecialchars($pb['name']); ?></span><?php endforeach; ?>
            <?php foreach ($discounts as $dc): ?><span class="dash-pill disc" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars(Discount_model::describe($dc['discount_type'], $dc['value'], $dc['max_discount'], $dc['min_order'])); ?>"><i class="bi bi-percent"></i> <?php echo htmlspecialchars($dc['name']); ?></span><?php endforeach; ?>
          </div>
        </section>
      </div>
    </div>

    <?php if ($show_activity): ?>
    <?php // ---------- Hoạt động gần đây (~20%) ---------- ?>
    <aside class="dash-activity">
      <header class="dash-card-head">
        <div><h6><span class="dash-live"></span> Hoạt động gần đây</h6><p>Tự cập nhật</p></div>
        <a href="<?php echo site_url('me/audit-logs'); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Xem Nhật ký hệ thống"><i class="bi bi-journal-text"></i></a>
      </header>
      <ul class="dash-feed" id="dashFeed" data-last="<?php echo $activity ? (int) $activity[0]['id'] : 0; ?>">
        <?php foreach ($activity as $a): ?>
          <li class="tone-<?php echo $a['tone']; ?>"><i class="bi <?php echo $a['icon']; ?>"></i><div><span><?php echo htmlspecialchars($a['text']); ?></span><time data-at="<?php echo htmlspecialchars($a['at']); ?>"></time></div></li>
        <?php endforeach; ?>
        <?php if ( ! $activity): ?><li class="dash-feed-empty">Chưa có hoạt động nào.</li><?php endif; ?>
      </ul>
    </aside>
    <?php endif; ?>
  </div>
</div>

<div class="dash-tooltip" id="dashTip" role="tooltip"></div>
<script>
(function(){
  // Chú thích khi rê chuột / chạm vào cột, thanh, đoạn màu (vùng chạm = cả ô, lớn hơn thanh).
  var tip = document.getElementById('dashTip');
  function show(el, x, y){ tip.innerHTML = el.dataset.tip; tip.style.display = 'block'; place(x, y); }
  function place(x, y){
    var w = tip.offsetWidth, h = tip.offsetHeight;
    var left = Math.min(Math.max(8, x - w / 2), window.innerWidth - w - 8);
    tip.style.left = left + 'px'; tip.style.top = Math.max(8, y - h - 12) + 'px';
  }
  document.querySelectorAll('[data-tip]').forEach(function(el){
    el.addEventListener('mouseenter', function(e){ show(el, e.clientX, e.clientY); el.classList.add('is-hover'); });
    el.addEventListener('mousemove', function(e){ place(e.clientX, e.clientY); });
    el.addEventListener('mouseleave', function(){ tip.style.display = 'none'; el.classList.remove('is-hover'); });
    el.addEventListener('click', function(e){ show(el, e.clientX, e.clientY); });
  });
  document.addEventListener('scroll', function(){ tip.style.display = 'none'; }, true);

  // Nút "Bảng": xem số liệu dạng bảng.
  document.querySelectorAll('.dash-table-toggle').forEach(function(b){
    b.addEventListener('click', function(){ document.getElementById(b.dataset.target).classList.toggle('d-none'); b.classList.toggle('active'); });
  });

  // Hoạt động: thời gian tương đối + tự làm mới mỗi 30 giây.
  var feed = document.getElementById('dashFeed');
  if ( ! feed) return;
  function ago(s){
    var t = new Date(s.replace(' ', 'T')), d = Math.floor((Date.now() - t.getTime()) / 1000);
    if (d < 60) return 'vừa xong';
    if (d < 3600) return Math.floor(d / 60) + ' phút trước';
    if (d < 86400) return Math.floor(d / 3600) + ' giờ trước';
    if (d < 7 * 86400) return Math.floor(d / 86400) + ' ngày trước';
    return ('0' + t.getDate()).slice(-2) + '/' + ('0' + (t.getMonth() + 1)).slice(-2) + '/' + t.getFullYear();
  }
  function stamp(){ feed.querySelectorAll('time[data-at]').forEach(function(el){ el.textContent = ago(el.dataset.at); el.title = el.dataset.at; }); }
  stamp();
  function esc(s){ var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function poll(){
    fetch('<?php echo base_url('me/dashboard/activity.html'); ?>?after=' + (feed.dataset.last || 0), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function(r){ return r.json(); })
      .then(function(res){
        if ( ! res.success || ! res.items.length) return;
        var empty = feed.querySelector('.dash-feed-empty'); if (empty) empty.remove();
        res.items.slice().reverse().forEach(function(a){
          var li = document.createElement('li');
          li.className = 'tone-' + a.tone + ' is-new';
          li.innerHTML = '<i class="bi ' + a.icon + '"></i><div><span>' + esc(a.text) + '</span><time data-at="' + a.at + '"></time></div>';
          feed.insertBefore(li, feed.firstChild);
        });
        feed.dataset.last = res.items[0].id;
        while (feed.children.length > 40) feed.removeChild(feed.lastChild);
        stamp();
      })
      .catch(function(){});
  }
  setInterval(function(){ poll(); stamp(); }, 30000);
})();
</script>
