(function () {
  'use strict';

  document.querySelectorAll('[data-toggle="sidebar"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('sidebar')?.classList.toggle('open');
    });
  });

  // Confirmação em ações destrutivas / irreversíveis (data-confirm="Mensagem").
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    var msg = form.getAttribute('data-confirm');
    if (!msg) return;
    if (form.dataset.confirmed === '1') return;
    e.preventDefault();
    if (window.Swal) {
      Swal.fire({
        title: 'Confirmar ação',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0b3d68',
      }).then(function (r) {
        if (r.isConfirmed) {
          form.dataset.confirmed = '1';
          form.submit();
        }
      });
    } else if (confirm(msg)) {
      form.dataset.confirmed = '1';
      form.submit();
    }
  });

  // Dropzone: clique para abrir o seletor de ficheiros + realce ao arrastar.
  document.querySelectorAll('.dropzone[data-input]').forEach(function (dz) {
    var input = document.getElementById(dz.dataset.input);
    if (!input) return;
    dz.addEventListener('click', function () { input.click(); });
    ['dragover', 'dragenter'].forEach(function (ev) {
      dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.remove('drag'); });
    });
    dz.addEventListener('drop', function (e) {
      if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        var label = dz.querySelector('.dz-filename');
        if (label) label.textContent = e.dataTransfer.files[0].name;
        var form = input.closest('form');
        if (form && dz.dataset.autosubmit === '1') form.submit();
      }
    });
    input.addEventListener('change', function () {
      var label = dz.querySelector('.dz-filename');
      if (label && input.files.length) label.textContent = input.files[0].name;
      var form = input.closest('form');
      if (form && dz.dataset.autosubmit === '1') form.submit();
    });
  });

  // Notificações (offcanvas) — carregadas via AJAX.
  var notifBell = document.getElementById('notifBell');
  if (notifBell) {
    notifBell.addEventListener('click', function () {
      fetch('/notificacoes/lista', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var list = document.getElementById('notifList');
          if (!data.length) {
            list.innerHTML = '<p class="text-muted text-center py-4">Sem notificações.</p>';
            return;
          }
          list.innerHTML = data.map(function (n) {
            return '<a href="' + (n.link_referencia || '#') + '" data-id="' + n.id + '" class="notif-item d-flex gap-2 py-2 border-bottom text-decoration-none ' + (n.lida == 1 ? 'opacity-50' : '') + '">' +
              '<i class="bi bi-dot fs-3 ' + (n.lida == 1 ? 'text-muted' : 'text-primary') + '"></i>' +
              '<div><div class="fw-semibold text-dark" style="font-size:13.5px">' + n.titulo + '</div>' +
              '<div class="small text-muted">' + n.mensagem + '</div>' +
              '<div class="small text-muted">' + n.data_hora_fmt + '</div></div></a>';
          }).join('');
        });
    });
  }

  document.addEventListener('click', function (e) {
    var item = e.target.closest('.notif-item');
    if (!item) return;
    var id = item.dataset.id;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch('/notificacoes/' + id + '/ler', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_csrf=' + encodeURIComponent(csrf || ''),
    });
  });

  // Auto-fecho de alerts flash após alguns segundos.
  document.querySelectorAll('.alert-flash').forEach(function (el) {
    setTimeout(function () {
      el.classList.add('fade');
      setTimeout(function () { el.remove(); }, 400);
    }, 6000);
  });
})();
