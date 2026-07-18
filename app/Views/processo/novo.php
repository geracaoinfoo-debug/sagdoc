<?php use App\Middleware\CsrfMiddleware; ?>
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="/dashboard">Painel</a></li>
    <li class="breadcrumb-item active">Novo Processo</li>
  </ol>
</nav>
<div class="page-title">Novo Processo Documental</div>
<div class="page-sub">Registe um processo associado a uma DU do SYDONIA (ASYCUDA)</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-soft card">
      <div class="card-header"><i class="bi bi-file-earmark-plus me-2"></i>Dados do Processo</div>
      <div class="card-body">
        <form id="formNovo" method="post" action="/processos" autocomplete="off">
          <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
          <div class="mb-3">
            <label class="form-label">Número da DU (SYDONIA) <span class="text-danger">*</span></label>
            <input class="form-control" id="npDU" name="numero_du" placeholder="ex: 2025/001234" required>
            <div class="form-text">Formato: AAAA/NNNNNN — a DU deve estar registada e paga no SYDONIA.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Importador <span class="text-danger">*</span></label>
            <select class="form-select" id="npImp" name="importador_id" required>
              <option value="">— Selecione —</option>
              <?php foreach ($importadores as $i): ?>
                <option value="<?= (int) $i['id'] ?>"><?= e($i['nome']) ?> (NIF <?= e($i['nif']) ?>)</option>
              <?php endforeach; ?>
              <option value="novo">+ Registar novo importador…</option>
            </select>
          </div>
          <div id="npNovoImp" class="row g-2 mb-3 d-none">
            <div class="col-8"><input class="form-control" name="novo_importador_nome" placeholder="Nome do importador"></div>
            <div class="col-4"><input class="form-control" name="novo_importador_nif" placeholder="NIF"></div>
          </div>
          <div class="row g-3">
            <div class="col-md-6 mb-3">
              <label class="form-label">Categoria de mercadoria <span class="text-danger">*</span></label>
              <select class="form-select" id="npCat" name="categoria" required>
                <option value="">— Selecione —</option>
                <?php foreach ($categorias as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Regime de despacho <span class="text-danger">*</span></label>
              <select class="form-select" name="regime" required>
                <option value="">— Selecione —</option>
                <?php foreach ($regimes as $r): ?><option><?= e($r) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Observações (opcional)</label>
            <textarea class="form-control" name="observacoes" rows="2"></textarea>
          </div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-right-circle me-1"></i> Criar e anexar documentos</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft card">
      <div class="card-header"><i class="bi bi-list-check me-2"></i>Checklist de documentos</div>
      <div class="card-body">
        <p class="text-muted small mb-2">Selecione a categoria para gerar automaticamente a lista de documentos obrigatórios (RF08).</p>
        <div id="npChecklist"><div class="text-muted small py-4 text-center">Aguardando categoria…</div></div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('npImp').addEventListener('change', function (e) {
  document.getElementById('npNovoImp').classList.toggle('d-none', e.target.value !== 'novo');
});
document.getElementById('npCat').addEventListener('change', function (e) {
  var box = document.getElementById('npChecklist');
  var categoria = e.target.value;
  if (!categoria) { box.innerHTML = '<div class="text-muted small py-4 text-center">Aguardando categoria…</div>'; return; }
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  fetch('/api/checklist', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: 'categoria=' + encodeURIComponent(categoria) + '&_csrf=' + encodeURIComponent(csrf)
  }).then(function (r) { return r.json(); }).then(function (data) {
    box.innerHTML = data.checklist.map(function (c) {
      return '<div class="checklist-item"><i class="bi bi-file-earmark-text text-azul"></i>' +
        '<span class="flex-grow-1">' + c.nome + '</span>' +
        '<span class="flag ' + (c.obrigatorio ? 'flag-ob' : 'flag-op') + '">' + (c.obrigatorio ? 'Obrigatório' : 'Opcional') + '</span></div>';
    }).join('');
  });
});
</script>
