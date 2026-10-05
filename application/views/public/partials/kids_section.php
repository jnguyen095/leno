<?php $kids_images = array_slice(array_values(array_filter($gallery, function($g) { return $g['category'] === 'kids'; })), 0, 4); ?>
<section class="pap-section" id="kids" aria-labelledby="kids-title">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 order-lg-2">
        <h2 id="kids-title" class="pap-section-title">Niềm vui của bé – an tâm của ba mẹ</h2>
        <p class="pap-section-text">
          Không gian vui chơi dành cho các bé, nơi các con có thể vận động, khám phá và vui chơi cùng bạn bè.
        </p>
        <a href="#location" class="btn pap-btn-primary btn-lg" onclick="papTrack('click_kids')">Ghé chơi ngay</a>
      </div>
      <div class="col-lg-6 order-lg-1">
        <div class="row g-2 pap-mini-gallery">
          <?php foreach ($kids_images as $img): ?>
            <div class="col-6">
              <img src="<?php echo base_url('assets/public/images/'.$img['image']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" loading="lazy" width="800" height="600">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
