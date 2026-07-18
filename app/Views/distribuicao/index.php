<?php use App\Middleware\CsrfMiddleware; ?>
<div class="page-title">Distribuição de Processos</div>
<div class="page-sub">Atribua processos submetidos aos verificadores (automático ou manual)</div>

<?php if (!$lista): ?>
<div class="card-soft card"><div class="card-body text-center text-muted py-5">Nenhum processo a aguardar distribuição.</div></div>
<?php else: ?>
<div class="card-soft card">
  <div class="card-header d-flex justify-content-between align-items-center">
    Processos aguardando distribuição
    <form action="/distribuicao/automatica" method="post">
      <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
      <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-lightning-charge"></i> Distribuir todos (automático)</button>
    </form>
  </div>
  <div class="card-body p-0">
    <table class="table tbl mb-0">
      <thead><tr><th>Nº DU</th><th>Importador</th><th>Categoria</th><th>Atribuir a</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $p): ?>
        <tr>
          <td><b><?= e($p['numero_du']) ?></b></td>
          <td><?= e($p['importador_nome']) ?></td>
          <td><?= e($p['categoria']) ?></td>
          <td>
            <form action="/processos/<?= (int) $p['id'] ?>/distribuir" method="post" class="d-flex gap-2">
              <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
              <select class="form-select form-select-sm" name="verificador_id">
                <?php foreach ($verificadores as $v): ?>
                  <option value="<?= (int) $v['id'] ?>"><?= e($v['nome']) ?> (<?= (int) $v['carga'] ?> na fila)</option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-primary text-nowrap">Atribuir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
