/**
 * Bấm vào ảnh xem trước (thumbnail/preview) để xem ảnh lớn dạng popup — dùng
 * lại Bootstrap Modal (đã load sẵn ở layout/footer.php), không cần thư viện
 * riêng. Chỉ gắn cho ảnh xem, KHÔNG dùng ở màn có ảnh là nút bấm chọn món
 * (POS/khách gọi món) vì sẽ đụng hành vi click hiện có ở đó.
 *
 * Dùng: onclick="papOpenImageLightbox(this)" trên thẻ <img>.
 */
(function (window) {
  function ensureModal() {
    var modal = document.getElementById('papImageLightboxModal');
    if (modal) return modal;

    var wrap = document.createElement('div');
    wrap.innerHTML =
      '<div class="modal fade" id="papImageLightboxModal" tabindex="-1">' +
        '<div class="modal-dialog modal-dialog-centered modal-lg">' +
          '<div class="modal-content bg-transparent border-0">' +
            '<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Đóng" style="z-index:10;"></button>' +
            '<img id="papImageLightboxImg" src="" class="w-100 rounded-4" style="object-fit:contain;max-height:85vh;">' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(wrap.firstElementChild);
    return document.getElementById('papImageLightboxModal');
  }

  window.papOpenImageLightbox = function (imgEl) {
    if (!imgEl || imgEl.classList.contains('d-none') || !imgEl.naturalWidth) return;

    var modalEl = ensureModal();
    document.getElementById('papImageLightboxImg').src = imgEl.src;
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  };
})(window);
