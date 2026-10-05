<div class="container-fluid py-3 py-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-fire text-danger"></i> Kitchen Display</h4>
    <span class="badge bg-secondary" id="lastUpdated">Đang tải...</span>
  </div>

  <ul class="nav nav-pills mb-3 d-lg-none" id="kdsTabs">
    <li class="nav-item"><button class="nav-link active" data-col="NEW">Mới <span class="badge bg-danger" id="countNEW">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-col="PREPARING">Đang pha chế <span class="badge bg-warning text-dark" id="countPREPARING">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-col="COMPLETED">Hoàn thành <span class="badge bg-success" id="countCOMPLETED">0</span></button></li>
  </ul>

  <div class="row g-3">
    <div class="col-lg-4 kds-col" data-col="NEW">
      <h6 class="text-danger fw-bold d-none d-lg-block"><i class="bi bi-exclamation-circle"></i> MỚI</h6>
      <div class="kds-column" id="colNEW"></div>
    </div>
    <div class="col-lg-4 kds-col d-none d-lg-block" data-col="PREPARING">
      <h6 class="text-warning fw-bold"><i class="bi bi-hourglass-split"></i> ĐANG PHA CHẾ</h6>
      <div class="kds-column" id="colPREPARING"></div>
    </div>
    <div class="col-lg-4 kds-col d-none d-lg-block" data-col="COMPLETED">
      <h6 class="text-success fw-bold"><i class="bi bi-check-circle"></i> HOÀN THÀNH</h6>
      <div class="kds-column" id="colCOMPLETED"></div>
    </div>
  </div>
</div>

<script>
var BASE_URL = '<?php echo base_url(); ?>';

document.querySelectorAll('#kdsTabs button').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('#kdsTabs button').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.kds-col').forEach(function(c){ c.classList.add('d-none'); c.classList.remove('d-lg-block'); });
    document.querySelector('.kds-col[data-col="'+btn.dataset.col+'"]').classList.remove('d-none');
  });
});

function ticketCard(t){
  var cls = 'status-'+t.status.toLowerCase();
  var itemsHtml = t.items.map(function(it){
    var thumb = it.image
      ? '<img class="kds-item-thumb" src="'+BASE_URL+'assets/'+it.image+'" alt="">'
      : '<div class="kds-item-thumb-fallback"><i class="bi bi-cup-straw"></i></div>';
    var name = escapeHtml(it.product_name) + (it.note ? ' <span class="kds-item-note">('+escapeHtml(it.note)+')</span>' : '');
    return '<div class="kds-item">'+thumb+
      '<div class="kds-item-name">'+name+'</div>'+
      '<div class="kds-item-qty">x'+it.qty+'</div>'+
    '</div>';
  }).join('');

  var actionBtn = '';
  if (t.status === 'NEW'){
    actionBtn = '<button class="btn btn-warning btn-sm w-100" onclick="advanceTicket('+t.id+',\'PREPARING\')">Bắt đầu pha chế</button>';
  } else if (t.status === 'PREPARING'){
    actionBtn = '<button class="btn btn-success btn-sm w-100" onclick="advanceTicket('+t.id+',\'COMPLETED\')">Hoàn thành</button>';
  }

  var label = t.table_name ? escapeHtml(t.table_name) : '<i class="bi bi-bag-check"></i> Mang đi';
  return '<div class="card kds-ticket '+cls+' mb-3">'+
    '<div class="card-header d-flex justify-content-between">'+
      '<span>'+label+'</span><span class="small text-muted">#'+escapeHtml(t.order_no)+'</span>'+
    '</div>'+
    '<div class="card-body">'+
      '<div class="mb-2">'+itemsHtml+'</div>'+
      '<div class="small text-muted mb-2">'+timeAgo(t.created_at)+'</div>'+
      actionBtn+
    '</div>'+
  '</div>';
}

function escapeHtml(s){ var d=document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

function timeAgo(dt){
  var then = new Date(dt.replace(' ','T'));
  var now = new Date();
  var diffMin = Math.floor((now.getTime() - then.getTime())/60000);

  var sameDay = then.getFullYear() === now.getFullYear() && then.getMonth() === now.getMonth() && then.getDate() === now.getDate();

  if (sameDay){
    if (diffMin < 1) return 'Vừa xong';
    if (diffMin < 60) return diffMin+' phút trước';
    var hours = Math.floor(diffMin/60);
    var mins = diffMin % 60;
    return hours+' giờ'+(mins > 0 ? ' '+mins+' phút' : '')+' trước';
  }

  var pad = function(n){ return n < 10 ? '0'+n : n; };
  return pad(then.getDate())+'/'+pad(then.getMonth()+1)+' '+pad(then.getHours())+':'+pad(then.getMinutes());
}

function loadTickets(){
  fetch('<?php echo base_url('api/kitchen/tickets'); ?>')
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (!res.success) return;
      var byStatus = {NEW:[], PREPARING:[], COMPLETED:[]};
      res.tickets.forEach(function(t){ byStatus[t.status].push(t); });

      ['NEW','PREPARING','COMPLETED'].forEach(function(s){
        document.getElementById('col'+s).innerHTML = byStatus[s].map(ticketCard).join('') || '<div class="text-muted text-center py-4">Không có ticket</div>';
        document.getElementById('count'+s).textContent = byStatus[s].length;
      });
      document.getElementById('lastUpdated').textContent = 'Cập nhật lúc '+new Date().toLocaleTimeString('vi-VN');
    });
}

function advanceTicket(id, status){
  fetch('<?php echo base_url('api/kitchen/ticket'); ?>/'+id+'/status', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({status: status})
  }).then(function(){ loadTickets(); });
}

loadTickets();
setInterval(loadTickets, 5000);
</script>
