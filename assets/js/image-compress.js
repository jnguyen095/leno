/**
 * Tự nén ảnh (VD: ảnh chụp trực tiếp từ điện thoại, thường 3-15MB) xuống tối
 * đa ~1MB ngay trên trình duyệt trước khi upload — server vẫn chặn ở 2MB
 * (xem *::_handle_image_upload() trong các controller Inventory_items.php/
 * Products.php/Recipes.php) nhưng ảnh chụp máy thường vượt xa mức đó, khiến
 * upload báo lỗi. Không cần GD/Imagick phía server.
 *
 * Dùng: gắn onchange="papHandleImageInput(this, 'previewImgId', 'statusElId')"
 * lên input[type=file]. statusElId có thể bỏ trống nếu không cần hiển thị
 * tiến trình nén.
 */
(function (window) {
  var MAX_BYTES = 1024 * 1024; // 1MB
  var MAX_DIM = 1920; // cạnh dài tối đa (px) — đủ nét cho web/in danh sách, không cần ảnh gốc máy ảnh

  function loadImage(file) {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () { resolve({ img: img, url: url }); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('load failed')); };
      img.src = url;
    });
  }

  function canvasToBlob(canvas, quality) {
    return new Promise(function (resolve) {
      canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', quality);
    });
  }

  /** Trả về File đã nén, hoặc chính $file gốc nếu không nén được / nén không nhỏ hơn. */
  function compressFile(file, maxBytes, maxDim) {
    if (!file.type || file.type.indexOf('image/') !== 0) {
      return Promise.resolve(file);
    }

    return loadImage(file).then(function (loaded) {
      var img = loaded.img;
      var w = img.naturalWidth, h = img.naturalHeight;
      var scale = Math.min(1, maxDim / Math.max(w, h));

      var canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(w * scale));
      canvas.height = Math.max(1, Math.round(h * scale));
      var ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      URL.revokeObjectURL(loaded.url);

      var quality = 0.85;

      function attempt(n) {
        return canvasToBlob(canvas, quality).then(function (blob) {
          if (!blob) return null;
          if (blob.size <= maxBytes || n >= 8) return blob;

          if (quality > 0.4) {
            quality -= 0.12;
          } else {
            canvas.width = Math.max(1, Math.round(canvas.width * 0.85));
            canvas.height = Math.max(1, Math.round(canvas.height * 0.85));
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          }
          return attempt(n + 1);
        });
      }

      return attempt(0);
    }).then(function (blob) {
      if (!blob || blob.size >= file.size) return file;
      var newName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
      return new File([blob], newName, { type: 'image/jpeg' });
    }).catch(function () {
      return file;
    });
  }

  window.papHandleImageInput = function (input, previewImgId, statusElId) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var previewImg = previewImgId ? document.getElementById(previewImgId) : null;
    var statusEl = statusElId ? document.getElementById(statusElId) : null;
    var form = input.form;
    var submitBtn = form ? form.querySelector('button[type=submit], button:not([type])') : null;

    if (previewImg) {
      previewImg.src = URL.createObjectURL(file);
      previewImg.classList.remove('d-none');
    }

    if (file.size <= MAX_BYTES) {
      if (statusEl) statusEl.textContent = '';
      return;
    }

    var originalKB = Math.round(file.size / 1024);
    if (statusEl) statusEl.textContent = 'Đang nén ảnh (' + originalKB + ' KB)...';
    if (submitBtn) submitBtn.disabled = true;

    compressFile(file, MAX_BYTES, MAX_DIM).then(function (result) {
      if (result !== file) {
        var dt = new DataTransfer();
        dt.items.add(result);
        input.files = dt.files;
        if (previewImg) previewImg.src = URL.createObjectURL(result);
        if (statusEl) statusEl.textContent = 'Đã nén ảnh: ' + originalKB + ' KB → ' + Math.round(result.size / 1024) + ' KB';
      } else if (statusEl) {
        statusEl.textContent = '';
      }
    }).finally(function () {
      if (submitBtn) submitBtn.disabled = false;
    });
  };
})(window);
