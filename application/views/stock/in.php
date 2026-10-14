<div class="container py-3 py-md-4" style="max-width:600px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-box-arrow-in-down text-success"></i> Nhập kho</h4>
    <a href="<?php echo site_url('me/stock/in/import'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-arrow-up"></i> Nhập theo file</a>
  </div>

  <?php if ($success): ?><div class="alert alert-success py-2 small"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="row g-2 mb-3">
    <div class="col-sm-6">
      <label class="form-label">Danh mục</label>
      <select id="categorySelect" class="form-select">
        <option value="">-- Chọn danh mục --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-6">
      <label class="form-label">Tìm sản phẩm</label>
      <input type="text" id="searchInput" class="form-control" placeholder="Tên hoặc SKU...">
    </div>
  </div>

  <?php echo form_open(current_url(), array('id' => 'stockInForm')); ?>
    <div id="itemsList"></div>
    <?php // Số đã nhập ở danh mục khác (đang không hiện) — giữ bằng input ẩn để vẫn gửi đi khi xác nhận. ?>
    <div id="carriedInputs"></div>
    <div id="carriedNote" class="small text-muted mb-2" style="display:none;"></div>
    <div id="noteWrap" class="mb-3" style="display:none;">
      <label class="form-label">Ghi chú (tuỳ chọn, áp dụng cho tất cả)</label>
      <input type="text" name="note" class="form-control" placeholder="Vd: nhập từ nhà cung cấp A">
    </div>
    <div id="submitWrap" style="display:none;">
      <button type="submit" class="btn btn-brand btn-lg"><i class="bi bi-check-lg"></i> Xác nhận nhập kho</button>
    </div>
  <?php echo form_close(); ?>
</div>

<script>
(function(){
  var BASE_URL = '<?php echo base_url(); ?>';
  var categorySelect = document.getElementById('categorySelect');
  var searchInput = document.getElementById('searchInput');
  var itemsList = document.getElementById('itemsList');
  var noteWrap = document.getElementById('noteWrap');
  var submitWrap = document.getElementById('submitWrap');
  var enteredQty = {}; // item_id -> giá trị đã gõ, giữ lại khi lọc lại danh sách
  var searchTimer = null;

  function fmt(n){ n = parseFloat(n); return (Math.round(n*100)/100).toString(); }

  function imageHtml(it){
    return it.image
      ? '<img src="'+BASE_URL+'assets/'+it.image+'" class="rounded border flex-shrink-0" style="width:48px;height:48px;object-fit:cover;cursor:zoom-in;" onclick="papOpenImageLightbox(this);">'
      : '<div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted flex-shrink-0" style="width:48px;height:48px;"><i class="bi bi-box-seam"></i></div>';
  }

  function renderItems(items){
    if (items.length === 0){
      itemsList.innerHTML = '<p class="text-muted small">Không tìm thấy sản phẩm.</p>';
      syncCarried([]);
      return;
    }
    itemsList.innerHTML = items.map(function(it, i){
      var val = enteredQty[it.id] || '';
      var lowStock = parseFloat(it.qty_on_hand) < parseFloat(it.low_stock_threshold);
      return '<div class="d-flex align-items-center gap-2 py-2 border-bottom">'+
        '<span class="stock-seq">'+(i + 1)+'</span>'+
        imageHtml(it)+
        '<div class="flex-grow-1">'+
          '<div class="fw-semibold">'+it.name+'</div>'+
          '<div class="small text-muted">Tồn: <span class="fw-semibold '+(lowStock ? 'text-danger' : 'text-success')+'">'+fmt(it.qty_on_hand)+'</span> '+it.unit_name+'</div>'+
        '</div>'+
        '<input type="number" step="0.01" min="0" name="qty['+it.id+']" data-id="'+it.id+'" class="form-control" style="width:110px;" placeholder="0" value="'+val+'">'+
      '</div>';
    }).join('');
    syncCarried(items.map(function(it){ return String(it.id); }));
    noteWrap.style.display = '';
    submitWrap.style.display = 'grid';
  }

  // Input ẩn cho số đã nhập ở các danh mục đang không hiện; nút xác nhận vẫn hiện khi còn số đã nhập.
  function syncCarried(shownIds){
    var box = document.getElementById('carriedInputs'), note = document.getElementById('carriedNote'), html = '', n = 0;
    Object.keys(enteredQty).forEach(function(id){
      if (shownIds.indexOf(String(id)) !== -1) return;
      html += '<input type="hidden" name="qty['+id+']" value="'+String(enteredQty[id]).replace(/"/g, '')+'">';
      n++;
    });
    box.innerHTML = html;
    note.style.display = n ? '' : 'none';
    note.innerHTML = n ? '<i class="bi bi-info-circle"></i> Đã nhập '+n+' sản phẩm ở danh mục khác — vẫn được lưu khi xác nhận.' : '';
    var any = shownIds.length > 0 || n > 0;
    noteWrap.style.display = any ? '' : 'none';
    submitWrap.style.display = any ? 'grid' : 'none';
  }

  function loadItems(){
    var categoryId = categorySelect.value;
    var keyword = searchInput.value.trim();
    if ( ! categoryId){
      itemsList.innerHTML = '<div class="stock-pick-hint"><i class="bi bi-funnel"></i> Chọn <b>Danh mục</b> để hiện danh sách sản phẩm.</div>';
      syncCarried([]);
      return;
    }
    itemsList.innerHTML = '<p class="text-muted small">Đang tải...</p>';
    fetch('<?php echo site_url('me/inventory/items/by-category'); ?>?category_id=' + encodeURIComponent(categoryId) + '&q=' + encodeURIComponent(keyword))
      .then(function(r){ return r.json(); })
      .then(renderItems);
  }

  itemsList.addEventListener('input', function(e){
    if (e.target.name && e.target.name.indexOf('qty[') === 0){
      var id = e.target.dataset.id;
      if (e.target.value === '') delete enteredQty[id]; else enteredQty[id] = e.target.value;
    }
  });

  categorySelect.addEventListener('change', loadItems);
  searchInput.addEventListener('input', function(){
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadItems, 300);
  });

  document.getElementById('stockInForm').addEventListener('submit', function(e){
    var btn = submitWrap.querySelector('button');
    if (btn.disabled){
      e.preventDefault(); // đã bấm rồi -> chặn bấm/gửi trùng lần 2
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
  });

  loadItems(); // chưa chọn danh mục -> hiện hướng dẫn chọn
})();
</script>
<script src="<?php echo asset_url('assets/js/image-lightbox.js'); ?>"></script>
