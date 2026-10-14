<?php if ( ! empty(get_instance()->admin_shell_open)): // đóng khung trang quản trị (mở ở header) ?>
  </div>
</div>
<?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php // Hộp xác nhận trong trang (thay confirm() của trình duyệt). Form có data-confirm="..." tự hỏi trước khi gửi. ?>
<div class="modal fade" id="appConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-body text-center pt-4 px-4">
        <div class="app-confirm-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="fs-6 fw-semibold mt-3" id="appConfirmTitle"></div>
        <div class="small text-muted mt-1" id="appConfirmMessage"></div>
      </div>
      <div class="modal-footer justify-content-center border-0 pt-2 pb-4 gap-2">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Huỷ</button>
        <button type="button" class="btn btn-danger px-4" id="appConfirmOk">Xoá</button>
      </div>
    </div>
  </div>
</div>
<script>
// appConfirm({title, message, ok}).then(function(yes){ ... }) — hỏi xác nhận bằng hộp thoại Bootstrap.
window.appConfirm = function(opts){
  return new Promise(function(resolve){
    var el = document.getElementById('appConfirmModal'), okBtn = document.getElementById('appConfirmOk');
    var modal = bootstrap.Modal.getOrCreateInstance(el), answered = false;
    document.getElementById('appConfirmTitle').textContent = opts.title || 'Bạn chắc chắn?';
    document.getElementById('appConfirmMessage').textContent = opts.message || '';
    okBtn.textContent = opts.ok || 'Xoá';
    okBtn.onclick = function(){ answered = true; modal.hide(); };
    el.addEventListener('hidden.bs.modal', function onHidden(){
      el.removeEventListener('hidden.bs.modal', onHidden);
      okBtn.onclick = null;
      resolve(answered);
    });
    el.addEventListener('shown.bs.modal', function onShown(){ el.removeEventListener('shown.bs.modal', onShown); okBtn.focus(); });
    modal.show();
  });
};
// Chú thích khi rê chuột: phần tử có data-bs-toggle="tooltip" title="...". Chỉ hiện khi rê chuột (không dính lại
// sau khi bấm / mở hộp xác nhận); máy cảm ứng không có rê chuột nên không hiện.
// Gắn ở body (selector) nên phần tử thêm sau (vd khối "Món đã gọi" render lại qua AJAX) cũng có chú thích.
new bootstrap.Tooltip(document.body, {selector: '[data-bs-toggle="tooltip"]', trigger: 'hover', container: 'body'});
document.addEventListener('click', function(e){
  var el = e.target.closest && e.target.closest('[data-bs-toggle="tooltip"]');
  if (el) { var t = bootstrap.Tooltip.getInstance(el); if (t) t.hide(); }
});
// <form data-confirm="Xoá ...?" data-confirm-message="..." data-confirm-ok="Xoá">: hỏi trước khi gửi.
document.addEventListener('submit', function(e){
  var f = e.target;
  if ( ! f.matches || ! f.matches('form[data-confirm]') || f.dataset.confirmed === '1') return;
  e.preventDefault();
  appConfirm({title: f.dataset.confirm, message: f.dataset.confirmMessage, ok: f.dataset.confirmOk}).then(function(yes){
    if ( ! yes) return;
    f.dataset.confirmed = '1';
    f.submit();
  });
});
</script>
</body>
</html>
