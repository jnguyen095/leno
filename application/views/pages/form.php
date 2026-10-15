<?php
  // Thêm / sửa trang nội dung website: mã (đường dẫn), tiêu đề, nội dung (CKEditor 5 — ảnh tải lên máy chủ
  // + chọn ảnh có sẵn từ Thư viện ảnh), bật / tắt. Thông tin tạo / sửa ở cột phải.
  $dt = function ($v) { return $v ? date('d/m/Y H:i', strtotime($v)) : '—'; };
  $ck = 'https://cdn.jsdelivr.net/npm/ckeditor5@43.3.1/dist';
?>
<link rel="stylesheet" href="<?php echo $ck; ?>/browser/ckeditor5.css">
<div class="container-xl py-3 py-md-4 settings-page" style="max-width:1200px">
  <a href="<?php echo site_url('me/pages'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Trang nội dung</a>
  <h4 class="fw-bold mb-3"><?php echo $page ? 'Sửa trang' : 'Thêm trang'; ?></h4>

  <?php if ($error): ?>
    <div class="alert alert-danger d-flex gap-2 align-items-start py-2 small"><i class="bi bi-exclamation-triangle-fill"></i><div><?php echo htmlspecialchars($error); ?></div></div>
  <?php elseif ( ! empty($success)): ?>
    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <?php echo form_open(current_url(), array('id' => 'pgForm')); ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <section class="settings-card">
        <div class="settings-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="pgTitle">Tiêu đề</label>
              <input type="text" name="title" id="pgTitle" class="form-control form-control-lg" maxlength="200" required placeholder="VD: Giới thiệu Leno"
                     value="<?php echo htmlspecialchars($values['title']); ?>">
            </div>
            <div class="col-12">
              <label class="form-label" for="pgCode">Mã trang <span class="text-muted fw-normal small">(dùng làm đường dẫn)</span></label>
              <div class="input-group">
                <span class="input-group-text small text-muted"><?php echo htmlspecialchars(rtrim(base_url(), '/')); ?>/page/</span>
                <input type="text" name="code" id="pgCode" class="form-control" maxlength="100" required placeholder="gioi-thieu"
                       pattern="[a-z0-9]+(-[a-z0-9]+)*" autocapitalize="off" autocomplete="off" spellcheck="false"
                       value="<?php echo htmlspecialchars($values['code']); ?>">
                <span class="input-group-text">.html</span>
              </div>
              <div class="form-text">Chữ thường không dấu, số và dấu gạch ngang. Tự gợi ý theo tiêu đề khi thêm trang mới.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Nội dung</label>
              <textarea name="description" id="pgDescription" class="form-control" rows="16"><?php echo htmlspecialchars($values['description']); ?></textarea>
              <div class="form-text"><i class="bi bi-image"></i> Kéo thả / dán ảnh vào nội dung để tải lên (ảnh lớn hơn <?php echo round($shrink_kb / 1024); ?> MB được tự thu nhỏ), hoặc bấm <b>Thư viện ảnh</b> trên thanh công cụ để chọn ảnh có sẵn.</div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="col-lg-4">
      <section class="settings-card pg-side">
        <div class="settings-card-body">
          <div class="settings-row">
            <div class="settings-row-text">
              <label class="settings-row-label" for="pgStatus">Hiện trên website</label>
              <div class="settings-row-help">Tắt: khách mở link sẽ báo không tìm thấy trang.</div>
            </div>
            <div class="settings-row-control">
              <div class="form-check form-switch settings-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" name="status" value="1" id="pgStatus" <?php echo $values['status'] ? 'checked' : ''; ?>>
              </div>
            </div>
          </div>

          <?php if ($page): ?>
            <div class="mt-3">
              <label class="form-label small text-muted mb-1">Link trang</label>
              <div class="input-group input-group-sm">
                <input type="text" class="form-control" id="pgLink" readonly value="<?php echo htmlspecialchars(site_url('page/'.$page['code'])); ?>">
                <button type="button" class="btn btn-outline-secondary" id="pgCopy" data-bs-toggle="tooltip" title="Chép link"><i class="bi bi-clipboard"></i></button>
                <a class="btn btn-outline-secondary" href="<?php echo site_url('page/'.$page['code']); ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip"
                   title="<?php echo (int) $page['status'] ? 'Xem trên website' : 'Xem trước (trang đang tắt)'; ?>"><i class="bi bi-box-arrow-up-right"></i></a>
              </div>
            </div>
            <dl class="pg-meta mt-3 mb-0">
              <dt>Ngày tạo</dt><dd><?php echo $dt($page['created_at']); ?></dd>
              <dt>Người tạo</dt><dd><?php echo htmlspecialchars($page['created_by_name']); ?></dd>
              <dt>Ngày sửa</dt><dd><?php echo $dt($page['updated_at']); ?></dd>
              <dt>Người sửa</dt><dd><?php echo htmlspecialchars($page['updated_by_name']); ?></dd>
              <dt>Lượt xem</dt><dd><i class="bi bi-eye text-muted"></i> <?php echo number_format((int) $page['view_count'], 0, ',', '.'); ?></dd>
              <dt>Xem gần nhất</dt><dd><?php echo $dt($page['last_viewed_at']); ?></dd>
            </dl>
          <?php else: ?>
            <div class="small text-muted mt-3"><i class="bi bi-info-circle"></i> Ngày tạo, người tạo được ghi khi lưu.</div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>

  <div class="pb-savebar">
    <a href="<?php echo site_url('me/pages'); ?>" class="btn btn-outline-secondary">Huỷ</a>
    <button class="btn btn-outline-primary" name="stay" value="1"><i class="bi bi-floppy"></i> Lưu &amp; sửa tiếp</button>
    <button class="btn btn-brand px-4"><i class="bi bi-check2"></i> Lưu trang</button>
  </div>
  <?php echo form_close(); ?>
</div>

<?php // Hộp chọn ảnh có sẵn (nút "Thư viện ảnh" trên thanh công cụ trình soạn thảo). ?>
<div class="modal fade" id="pgImgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-images"></i> Thư viện ảnh</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
          <div class="small text-muted">Bấm vào ảnh để chèn vào nội dung.</div>
          <label class="btn btn-sm btn-brand mb-0"><i class="bi bi-upload"></i> Tải ảnh lên
            <input type="file" id="pgImgFile" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
          </label>
        </div>
        <div id="pgImgMsg" class="small text-danger mb-2 d-none"></div>
        <div id="pgImgGrid" class="pg-img-grid"></div>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo asset_url('assets/js/image-compress.js'); ?>"></script>
<script type="importmap">
{ "imports": { "ckeditor5": "<?php echo $ck; ?>/browser/ckeditor5.js", "ckeditor5/translations/vi.js": "<?php echo $ck; ?>/translations/vi.js" } }
</script>
<script type="module">
import {
  ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough, FontColor, FontSize, Alignment,
  Link, List, Indent, IndentBlock, BlockQuote, HorizontalLine, Table, TableToolbar, TableProperties, TableCellProperties,
  Image, ImageToolbar, ImageCaption, ImageStyle, ImageResize, ImageUpload, ImageInsert, ImageInsertViaUrl, LinkImage, AutoImage,
  MediaEmbed, PasteFromOffice, Autoformat, SourceEditing, Plugin, ButtonView, icons
} from 'ckeditor5';
import viTranslations from 'ckeditor5/translations/vi.js';

const UPLOAD_URL = <?php echo json_encode(site_url('me/pages/images/upload')); ?>;
const LIST_URL = <?php echo json_encode(site_url('me/pages/images/list')); ?>;
const CSRF_NAME = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
const CSRF_HASH = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
const SHRINK_BYTES = <?php echo (int) $shrink_kb * 1024; ?>;

// Ảnh lớn hơn 2MB: thu nhỏ ngay trên trình duyệt (assets/js/image-compress.js) trước khi gửi.
function shrink(file) {
  return file.size > SHRINK_BYTES && window.papCompressImage ? window.papCompressImage(file, SHRINK_BYTES, 1920) : Promise.resolve(file);
}

// Tải 1 ảnh lên máy chủ (thu nhỏ trước nếu > 2MB), trả về {url, name}.
function uploadImage(file, onProgress) {
  return shrink(file).then(file => new Promise((resolve, reject) => {
    const fd = new FormData();
    fd.append('upload', file);
    fd.append(CSRF_NAME, CSRF_HASH);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', UPLOAD_URL);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.responseType = 'json';
    if (onProgress) xhr.upload.onprogress = e => { if (e.lengthComputable) onProgress(e.loaded, e.total); };
    xhr.onload = () => {
      const r = xhr.response;
      if (xhr.status === 200 && r && r.url) resolve(r);
      else reject((r && r.error && r.error.message) || 'Không tải được ảnh "' + file.name + '".');
    };
    xhr.onerror = () => reject('Lỗi mạng khi tải ảnh.');
    xhr.send(fd);
  }));
}

// Ảnh kéo thả / dán / chọn từ máy trong trình soạn thảo: tải lên kèm mã CSRF.
function UploadAdapter(editor) {
  editor.plugins.get('FileRepository').createUploadAdapter = loader => ({
    upload: () => loader.file.then(file => uploadImage(file, (done, total) => { loader.uploadTotal = total; loader.uploaded = done; })
      .then(r => ({ default: r.url }))),
    abort: () => {}
  });
}

// Nút "Thư viện ảnh": mở hộp chọn ảnh đã tải lên trước đó.
class ImageLibrary extends Plugin {
  init() {
    this.editor.ui.componentFactory.add('imageLibrary', locale => {
      const btn = new ButtonView(locale);
      btn.set({ label: 'Thư viện ảnh', icon: icons.image, tooltip: true, withText: true });
      btn.on('execute', () => openLibrary(this.editor));
      return btn;
    });
  }
}

const modalEl = document.getElementById('pgImgModal');
const grid = document.getElementById('pgImgGrid');
const msg = document.getElementById('pgImgMsg');
let libraryEditor = null;

function showMsg(text) { msg.textContent = text || ''; msg.classList.toggle('d-none', ! text); }

function loadLibrary() {
  grid.innerHTML = '<div class="text-muted small">Đang tải...</div>';
  fetch(LIST_URL, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(d => {
      grid.innerHTML = '';
      if ( ! d.images.length) { grid.innerHTML = '<div class="text-muted small">Chưa có ảnh nào. Bấm <b>Tải ảnh lên</b>.</div>'; return; }
      d.images.forEach(img => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'pg-img-item';
        b.title = img.name + (img.width ? ' · ' + img.width + '×' + img.height : '');
        b.innerHTML = '<img loading="lazy" alt="">';
        b.querySelector('img').src = img.url;
        b.addEventListener('click', () => {
          libraryEditor.execute('insertImage', { source: img.url });
          bootstrap.Modal.getInstance(modalEl).hide();
          libraryEditor.editing.view.focus();
        });
        grid.appendChild(b);
      });
    })
    .catch(() => { grid.innerHTML = '<div class="text-danger small">Không tải được danh sách ảnh.</div>'; });
}

function openLibrary(editor) {
  libraryEditor = editor;
  showMsg('');
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
  loadLibrary();
}

document.getElementById('pgImgFile').addEventListener('change', async e => {
  const files = Array.from(e.target.files);
  e.target.value = '';
  showMsg('');
  for (const f of files) {
    try { await uploadImage(f); } catch (err) { showMsg(err); }
  }
  loadLibrary();
});

ClassicEditor.create(document.getElementById('pgDescription'), {
  licenseKey: 'GPL',
  language: 'vi',
  translations: [viTranslations],
  plugins: [
    Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough, FontColor, FontSize, Alignment,
    Link, List, Indent, IndentBlock, BlockQuote, HorizontalLine, Table, TableToolbar, TableProperties, TableCellProperties,
    Image, ImageToolbar, ImageCaption, ImageStyle, ImageResize, ImageUpload, ImageInsert, ImageInsertViaUrl, LinkImage, AutoImage,
    MediaEmbed, PasteFromOffice, Autoformat, SourceEditing, UploadAdapter, ImageLibrary
  ],
  toolbar: {
    items: [
      'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'fontColor', 'fontSize', '|',
      'alignment', 'bulletedList', 'numberedList', 'outdent', 'indent', '|',
      'link', 'insertImage', 'imageLibrary', 'mediaEmbed', 'insertTable', 'blockQuote', 'horizontalLine', '|', 'sourceEditing'
    ],
    shouldNotGroupWhenFull: true // xuống dòng thay vì gom vào nút "Xem thêm" — thấy hết công cụ
  },
  image: {
    insert: { integrations: ['upload', 'url'] },
    toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'toggleImageCaption', 'imageTextAlternative', 'linkImage', '|', 'resizeImage']
  },
  table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'] },
  link: { defaultProtocol: 'https://', decorators: { openInNewTab: { mode: 'manual', label: 'Mở ở tab mới', attributes: { target: '_blank', rel: 'noopener' } } } },
  mediaEmbed: { previewsInData: true }
}).then(editor => {
  window.pgEditor = editor;
  // Nhắc khi rời trang mà chưa lưu.
  const form = document.getElementById('pgForm');
  const initial = editor.getData();
  let dirty = false, submitting = false;
  editor.model.document.on('change:data', () => { dirty = editor.getData() !== initial; });
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('submit', () => { submitting = true; editor.updateSourceElement(); });
  window.addEventListener('beforeunload', e => { if (dirty && ! submitting) { e.preventDefault(); e.returnValue = ''; } });
}).catch(err => {
  console.error(err);
  const box = document.createElement('div');
  box.className = 'alert alert-warning py-2 small mt-2';
  box.textContent = 'Không tải được trình soạn thảo (kiểm tra kết nối mạng). Vẫn có thể nhập nội dung dạng HTML.';
  document.getElementById('pgDescription').after(box);
});
</script>

<script>
(function(){
  // Gợi ý mã trang theo tiêu đề (chỉ khi thêm mới và chưa tự sửa mã).
  var title = document.getElementById('pgTitle'), code = document.getElementById('pgCode');
  var auto = <?php echo $page || $values['code'] !== '' ? 'false' : 'true'; ?>;
  var slug = function(s){
    return s.toLowerCase().replace(/đ/g, 'd').normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 100);
  };
  title.addEventListener('input', function(){ if (auto) code.value = slug(title.value); });
  code.addEventListener('input', function(){ auto = false; code.value = code.value.toLowerCase().replace(/\s+/g, '-'); });

  var copy = document.getElementById('pgCopy');
  if (copy) copy.addEventListener('click', function(){
    var input = document.getElementById('pgLink');
    (navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.reject()).catch(function(){ input.select(); document.execCommand('copy'); });
    copy.innerHTML = '<i class="bi bi-check2"></i>';
    setTimeout(function(){ copy.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
  });
})();
</script>
