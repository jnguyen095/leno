<?php if ( ! empty($promotions)): ?>
<section class="pap-section" id="today" aria-labelledby="today-title">
  <div class="container">
    <h2 id="today-title" class="pap-section-title text-center">Hôm nay có gì?</h2>
    <div class="row g-4 mt-2">
      <?php foreach ($promotions as $p): ?>
        <div class="col-md-4">
          <article class="pap-promo-card">
            <?php if ( ! empty($p['image'])): ?>
              <img src="<?php echo base_url('assets/public/images/'.$p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="pap-promo-img" loading="lazy" width="800" height="600">
            <?php endif; ?>
            <div class="pap-promo-body">
              <h3 class="pap-promo-title"><?php echo htmlspecialchars($p['title']); ?></h3>
              <?php if ( ! empty($p['description'])): ?>
                <p class="pap-promo-desc"><?php echo htmlspecialchars($p['description']); ?></p>
              <?php endif; ?>
              <?php if ( ! empty($p['start_date']) || ! empty($p['end_date'])): ?>
                <div class="pap-promo-dates">
                  <i class="bi bi-calendar-event"></i>
                  <?php
                    if ($p['start_date']) echo date('d/m', strtotime($p['start_date']));
                    if ($p['start_date'] && $p['end_date']) echo ' – ';
                    if ($p['end_date']) echo date('d/m', strtotime($p['end_date']));
                  ?>
                </div>
              <?php endif; ?>
              <?php if ( ! empty($p['link'])): ?>
                <a href="#<?php echo htmlspecialchars($p['link']); ?>" class="pap-card-link">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
              <?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
