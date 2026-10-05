<div class="container py-3 py-md-4" style="max-width:600px;">
  <a href="<?php echo site_url('me/pha-che'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Pha chế</a>

  <?php if ($recipe['image']): ?>
    <img src="<?php echo base_url('assets/'.$recipe['image']); ?>" class="w-100 rounded-4 shadow-sm mb-3" style="max-height:260px;object-fit:cover;" alt="">
  <?php endif; ?>

  <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($recipe['name']); ?></h4>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <label class="form-label fw-semibold">Số mẻ</label>
      <div class="btn-group w-100 mb-2" role="group">
        <?php foreach (array(1, 2, 3, 4, 5) as $m): ?>
          <input type="radio" class="btn-check" name="multiplier" id="mult<?php echo $m; ?>" value="<?php echo $m; ?>" <?php echo $m === 1 ? 'checked' : ''; ?>>
          <label class="btn btn-outline-brand" for="mult<?php echo $m; ?>">x<?php echo $m; ?></label>
        <?php endforeach; ?>
      </div>
      <div class="input-group">
        <span class="input-group-text">Tuỳ chỉnh</span>
        <input type="number" min="0.5" step="0.5" id="customMultiplier" class="form-control" placeholder="VD: 2.5">
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-list-ul"></i> Thành phần</h6>
      <table class="table table-sm align-middle mb-0">
        <tbody>
        <?php foreach ($ingredients as $ing): ?>
          <tr>
            <td>
              <?php echo htmlspecialchars($ing['item_name']); ?>
              <?php if ($ing['source_recipe_id']): ?><span class="badge bg-info-subtle text-info-emphasis">sơ chế</span><?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
              <span class="fw-semibold ing-qty" data-base="<?php echo (float) $ing['quantity']; ?>"><?php echo rtrim(rtrim(number_format($ing['quantity'], 3, '.', ''), '0'), '.'); ?></span>
              <?php echo htmlspecialchars($ing['unit_label']); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($ingredients)): ?>
          <tr><td colspan="2" class="text-center text-muted py-3">Chưa có thành phần nào.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ( ! empty($steps)): ?>
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-list-ol"></i> Cách làm</h6>
      <ol class="mb-0">
        <?php foreach ($steps as $step): ?>
          <li><?php echo htmlspecialchars($step); ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
(function(){
  var qtyEls = document.querySelectorAll('.ing-qty');
  var radios = document.querySelectorAll('input[name=multiplier]');
  var customInput = document.getElementById('customMultiplier');

  function fmt(n){
    var s = n.toFixed(3).replace(/0+$/, '').replace(/\.$/, '');
    return s === '' || s === '-0' ? '0' : s;
  }
  function applyMultiplier(m){
    if ( ! (m > 0)) return;
    qtyEls.forEach(function(el){
      var base = parseFloat(el.dataset.base) || 0;
      el.textContent = fmt(base * m);
    });
  }
  radios.forEach(function(r){
    r.addEventListener('change', function(){
      customInput.value = '';
      applyMultiplier(parseFloat(r.value));
    });
  });
  customInput.addEventListener('input', function(){
    var v = parseFloat(this.value);
    if (v > 0){
      radios.forEach(function(r){ r.checked = false; });
      applyMultiplier(v);
    }
  });
})();
</script>
