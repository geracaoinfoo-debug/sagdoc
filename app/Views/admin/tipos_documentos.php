<?php use App\Middleware\CsrfMiddleware; ?>
<div class="page-title">Tipos de Documento</div>
<div class="page-sub">Documentos e a que categorias de mercadoria são obrigatórios (RF42/RF43)</div>

<div class="card-soft card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    Tipos configurados
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoTipo"><i class="bi bi-plus-lg"></i> Novo tipo</button>
  </div>
  <div class="card-body p-0">
    <table class="table tbl mb-0">
      <thead><tr><th>Documento</th><th>Obrigatório para</th><th>Validade (meses)</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $t): $obrig = json_decode($t['obrigatorio_para'], true) ?? []; ?>
        <tr>
          <td><?= e($t['nome']) ?><br><small class="text-muted"><?= e($t['descricao'] ?? '') ?></small></td>
          <td>
            <?php if (in_array('*', $obrig, true)): ?>
              <span class="badge bg-danger">Todas as categorias</span>
            <?php elseif ($obrig): ?>
              <?php foreach ($obrig as $c): ?><span class="badge bg-secondary me-1"><?= e($c) ?></span><?php endforeach; ?>
            <?php else: ?>
              <span class="text-muted">Opcional</span>
            <?php endif; ?>
          </td>
          <td><?= $t['validade_meses'] ? (int) $t['validade_meses'] . ' meses' : '—' ?></td>
          <td><span class="badge bg-<?= $t['ativo'] ? 'success' : 'secondary' ?>"><?= $t['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTipo<?= (int) $t['id'] ?>"><i class="bi bi-pencil"></i></button></td>
        </tr>

        <div class="modal fade" id="modalTipo<?= (int) $t['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="/admin/tipos-documentos/<?= (int) $t['id'] ?>" method="post">
                <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
                <div class="modal-header bg-azul text-white">
                  <h5 class="modal-title">Editar tipo de documento</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-2"><label class="form-label">Nome</label><input class="form-control" name="nome" value="<?= e($t['nome']) ?>" required></div>
                  <div class="mb-2"><label class="form-label">Descrição</label><input class="form-control" name="descricao" value="<?= e($t['descricao'] ?? '') ?>"></div>
                  <div class="mb-2"><label class="form-label">Obrigatório para (categorias separadas por vírgula, ou * para todas)</label><input class="form-control" name="obrigatorio_para" value="<?= e(implode(', ', $obrig)) ?>"></div>
                  <div class="mb-2"><label class="form-label">Validade (meses, opcional)</label><input class="form-control" type="number" name="validade_meses" value="<?= e((string) ($t['validade_meses'] ?? '')) ?>"></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="ativo" id="tativo<?= (int) $t['id'] ?>" <?= $t['ativo'] ? 'checked' : '' ?>><label class="form-check-label" for="tativo<?= (int) $t['id'] ?>">Ativo</label></div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalNovoTipo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/admin/tipos-documentos" method="post">
        <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
        <div class="modal-header bg-azul text-white">
          <h5 class="modal-title">Novo tipo de documento</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
          <div class="mb-2"><label class="form-label">Descrição</label><input class="form-control" name="descricao"></div>
          <div class="mb-2"><label class="form-label">Obrigatório para (categorias separadas por vírgula, ou * para todas)</label><input class="form-control" name="obrigatorio_para" placeholder="ex: Alimentos, Animais"></div>
          <div class="mb-2"><label class="form-label">Validade (meses, opcional)</label><input class="form-control" type="number" name="validade_meses"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Criar</button>
        </div>
      </form>
    </div>
  </div>
</div>
