/**
 * Danh sách sắp xếp được (dùng chung: Danh mục, Quản lý bàn...). Đổi thứ tự bằng kéo thả (nắm biểu tượng ⋮⋮, chuột
 * hoặc ngón tay — cần SortableJS), gõ số vị trí, hoặc nút lên / xuống; rồi bấm "Lưu thứ tự" (form bao quanh gửi ids[]).
 *
 * HTML:
 *   <form ...> <ul class="sort-list" data-sort-list data-bar="#idThanhLuu" data-post-form="#idFormThaoTac">
 *     <li class="sort-list-item" data-id="ID"> <input type="hidden" name="ids[]" value="ID">
 *       <span class="sort-list-handle">…</span> <input class="sort-list-pos"> … <button class="js-up"> <button class="js-down">
 *       <button class="js-post" data-action="URL" data-confirm="Câu hỏi?" data-confirm-message="…" data-confirm-ok="Xoá">
 *     </li> … </ul>
 *   <div id="idThanhLuu" hidden> … <button type="button" class="js-sort-reset"> <button>Lưu thứ tự</button> </div> </form>
 *   <form id="idFormThaoTac" class="d-none" data-confirm=""> (form riêng cho nút thao tác — form không lồng nhau được)
 */
(function(){
  'use strict';

  function init(list){
    var bar = document.querySelector(list.dataset.bar);
    var form = list.closest('form');
    var items = function(){ return Array.prototype.slice.call(list.children); };
    var order = function(){ return items().map(function(li){ return li.dataset.id; }).join(','); };
    var initial = order(), dirty = false, saving = false;

    // Đánh lại số vị trí 1..n theo thứ tự đang hiện; hiện thanh "Lưu thứ tự" khi khác lúc tải trang.
    function refresh(){
      var all = items();
      all.forEach(function(li, i){
        var pos = li.querySelector('.sort-list-pos'); if (pos) pos.value = i + 1;
        var up = li.querySelector('.js-up'), down = li.querySelector('.js-down');
        if (up) up.disabled = i === 0;
        if (down) down.disabled = i === all.length - 1;
      });
      dirty = order() !== initial;
      if (bar) bar.hidden = ! dirty;
    }
    function flash(li){ li.classList.remove('is-moved'); void li.offsetWidth; li.classList.add('is-moved'); }

    if (window.Sortable) {
      // Chỉ kéo ở tay nắm ⋮⋮ — cuộn trang trên điện thoại không vô tình kéo dòng.
      window.Sortable.create(list, { handle: '.sort-list-handle', animation: 150, ghostClass: 'is-ghost', chosenClass: 'is-chosen',
        onStart: function(){ document.querySelectorAll('.tooltip').forEach(function(t){ t.remove(); }); },
        onEnd: function(e){ refresh(); flash(e.item); } });
    }

    list.addEventListener('click', function(e){
      var btn = e.target.closest('.js-up, .js-down, .js-post'); if ( ! btn || ! list.contains(btn)) return;
      var li = btn.closest('.sort-list-item');
      if (btn.classList.contains('js-post')) {
        // Nút thao tác (xoá / ẩn / đặt lại...): gửi bằng form riêng, qua hộp xác nhận chung (form[data-confirm]).
        var f = document.querySelector(list.dataset.postForm); if ( ! f) return;
        f.action = btn.dataset.action;
        f.dataset.confirm = btn.dataset.confirm || 'Bạn chắc chắn?';
        f.dataset.confirmMessage = btn.dataset.confirmMessage || '';
        f.dataset.confirmOk = btn.dataset.confirmOk || 'Đồng ý';
        if (f.requestSubmit) f.requestSubmit(); else f.dispatchEvent(new Event('submit', { cancelable: true }));
        return;
      }
      if (btn.classList.contains('js-up') && li.previousElementSibling) list.insertBefore(li, li.previousElementSibling);
      if (btn.classList.contains('js-down') && li.nextElementSibling) list.insertBefore(li.nextElementSibling, li);
      refresh(); flash(li); btn.focus();
    });

    // Gõ số vị trí: chuyển dòng tới vị trí đó khi rời ô / bấm Enter.
    list.addEventListener('change', function(e){
      if ( ! e.target.classList.contains('sort-list-pos')) return;
      var li = e.target.closest('.sort-list-item'), all = items();
      var to = Math.max(1, Math.min(all.length, parseInt(e.target.value, 10) || 1)) - 1;
      all.splice(all.indexOf(li), 1);
      list.insertBefore(li, all[to] || null);
      refresh(); flash(li);
    });
    list.addEventListener('keydown', function(e){
      if (e.target.classList.contains('sort-list-pos') && e.key === 'Enter') { e.preventDefault(); e.target.blur(); }
    });

    var reset = bar && bar.querySelector('.js-sort-reset');
    if (reset) reset.addEventListener('click', function(){
      initial.split(',').forEach(function(id){ list.appendChild(list.querySelector('[data-id="' + id + '"]')); });
      refresh();
    });
    // Nhắc khi rời trang mà chưa lưu.
    if (form) form.addEventListener('submit', function(){ saving = true; });
    window.addEventListener('beforeunload', function(e){ if (dirty && ! saving) { e.preventDefault(); e.returnValue = ''; } });
    refresh();
  }

  document.querySelectorAll('[data-sort-list]').forEach(init);
})();
