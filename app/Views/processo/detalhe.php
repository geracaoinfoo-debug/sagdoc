<?php

use App\Middleware\CsrfMiddleware;
use App\Services\ChecklistService;
use App\Services\SLAService;

$sla = SLAService::status($processo);
$isDono = (int) $usuario['id'] === (int) $processo['despachante_id'];
$isVerificadorAtribuido = $usuario['perfil'] === 'verificador' && (int) ($processo['verificador_id'] ?? 0) === (int) $usuario['id'];
$completos = ChecklistService::obrigatoriosCompletos($processo);
$podeUpload = $isDono && in_array($processo['status'], ['rascunho', 'aguardando_documentos', 'rejeitado'], true);
$podeComunicar = in_array((int) $usuario['id'], [(int) $processo['despachante_id'], (int) ($processo['verificador_id'] ?? 0)], true)
    || in_array($usuario['perfil'], ['chefe_setor', 'administrador'], true);
$csrf = CsrfMiddleware::token();
?>
<a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Voltar</a>
<div class="page-title">Processo <?= e($processo['numero_du']) ?></div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-soft card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Dados do Processo</span>
        <span class="badge-st st-<?= e($processo['status']) ?>"><?= e(status_label($processo['status'])) ?></span>
      </div>
      <div class="card-body">
        <div class="row g-2" style="font-size:14px">
          <div class="col-6"><small class="text-muted d-block">Nº DU (SYDONIA)</small><b><?= e($processo['numero_du']) ?></b></div>
          <div class="col-6"><small class="text-muted d-block">Categoria</small><b><?= e($processo['categoria']) ?></b></div>
          <div class="col-6"><small class="text-muted d-block">Importador</small><b><?= e($processo['importador_nome']) ?></b><br><small>NIF <?= e($processo['importador_nif']) ?></small></div>
          <div class="col-6"><small class="text-muted d-block">Regime</small><b><?= e($processo['regime']) ?></b></div>
          <div class="col-6"><small class="text-muted d-block">Despachante</small><b><?= e($processo['despachante_nome']) ?></b></div>
          <div class="col-6"><small class="text-muted d-block">Verificador</small><b><?= e($processo['verificador_nome'] ?? '—') ?></b></div>
          <div class="col-6"><small class="text-muted d-block">Criado em</small><?= fmt_datahora($processo['data_criacao']) ?></div>
          <div class="col-6"><small class="text-muted d-block">SLA (<?= e($sla['fase']) ?>)</small><span class="sla-dot sla-<?= e($sla['cor']) ?>"></span><?= e($sla['label']) ?></div>
        </div>
        <?php if ($processo['observacoes']): ?>
          <div class="mt-2"><small class="text-muted d-block">Observações</small><?= e($processo['observacoes']) ?></div>
        <?php endif; ?>
        <?php if ($processo['parecer_tecnico']): ?>
          <div class="alert alert-success mt-3 mb-0 py-2"><b>Parecer do verificador:</b><br><?= nl2br(e($processo['parecer_tecnico'])) ?></div>
        <?php endif; ?>
        <?php if ($processo['motivo_rejeicao']): ?>
          <div class="alert alert-danger mt-3 mb-0 py-2"><b>Motivo de rejeição / documentos pedidos:</b><br><?= nl2br(e($processo['motivo_rejeicao'])) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-soft card mb-4">
      <div class="card-header"><i class="bi bi-paperclip me-2"></i>Documentos &amp; Checklist</div>
      <div class="card-body">
        <?php foreach ($checklist as $item): ?>
          <div class="checklist-item">
            <i class="bi <?= $item['enviado'] ? 'bi-check-circle-fill doc-ok' : 'bi-circle doc-miss' ?>"></i>
            <div class="flex-grow-1">
              <div><?= e($item['nome']) ?> <span class="flag <?= $item['obrigatorio'] ? 'flag-ob' : 'flag-op' ?>"><?= $item['obrigatorio'] ? 'Obrigatório' : 'Opcional' ?></span></div>
              <?php foreach ($item['documentos'] as $doc): ?>
                <small class="text-muted d-block">
                  <i class="bi bi-file-earmark"></i> <?= e($doc['nome_arquivo']) ?> (<?= fmt_bytes((int) $doc['tamanho_bytes']) ?>)
                  <a href="/documentos/<?= (int) $doc['id'] ?>" class="ms-1" target="_blank">descarregar</a>
                  <?php if ($podeUpload): ?>
                    <form action="/processos/<?= (int) $processo['id'] ?>/documentos/<?= (int) $doc['id'] ?>" method="post" class="d-inline" data-confirm="Remover este documento?">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="_method" value="DELETE">
                      <button type="submit" class="btn btn-link btn-sm p-0 text-danger">remover</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($isVerificadorAtribuido): ?>
                    <form action="/documentos/<?= (int) $doc['id'] ?>/marcar-verificado" method="post" class="d-inline">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                      <button type="submit" class="btn btn-link btn-sm p-0 ms-1"><?= $doc['verificado'] ? '✔ verificado' : 'marcar verificado' ?></button>
                    </form>
                  <?php endif; ?>
                </small>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ($podeUpload): ?>
          <?php foreach ($checklist as $item): ?>
            <form action="/processos/<?= (int) $processo['id'] ?>/documentos" method="post" enctype="multipart/form-data" class="row g-2 align-items-center mb-2">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="tipo_documento_id" value="<?= (int) $item['id'] ?>">
              <div class="col-auto small text-muted" style="min-width:220px">Anexar a <b><?= e($item['nome']) ?></b>:</div>
              <div class="col-auto"><input type="file" name="arquivo" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required></div>
              <div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i> Carregar</button></div>
            </form>
          <?php endforeach; ?>
          <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle"></i> PDF, JPG, PNG — máximo 10MB por ficheiro (RF09).</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-soft card">
      <div class="card-header"><i class="bi bi-chat-dots me-2"></i>Comunicações (RF27)</div>
      <div class="card-body">
        <div style="max-height:280px;overflow-y:auto">
          <?php if (!$mensagens): ?>
            <p class="text-muted small text-center py-3">Sem mensagens neste processo.</p>
          <?php endif; ?>
          <?php foreach ($mensagens as $m): $me = (int) $m['remetente_id'] === (int) $usuario['id']; ?>
            <div class="msg <?= $me ? 'me' : 'them' ?>">
              <?= nl2br(e($m['mensagem'])) ?>
              <div class="meta"><?= e(explode(' ', $m['remetente_nome'])[0]) ?> · <?= fmt_datahora($m['data_hora']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($podeComunicar): ?>
          <form action="/processos/<?= (int) $processo['id'] ?>/mensagens" method="post" class="input-group mt-2">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input class="form-control" name="mensagem" placeholder="Escrever mensagem…" required>
            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-soft card mb-4">
      <div class="card-header"><i class="bi bi-gear me-2"></i>Ações</div>
      <div class="card-body">
        <?php if ($isDono && in_array($processo['status'], ['rascunho', 'aguardando_documentos', 'rejeitado'], true)): ?>
            <?php if ($processo['status'] === 'rejeitado' && (int) $processo['tentativas_submissao'] >= 3): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-octagon"></i> Este processo já foi rejeitado 3 vezes. É necessária a intervenção do Chefe de Setor.</div>
            <?php else: ?>
                <p class="small text-muted">
                    <?php if ($completos): ?>
                        <span class="text-success"><i class="bi bi-check-circle"></i> Todos os documentos obrigatórios anexados.</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="bi bi-exclamation-circle"></i> Faltam documentos obrigatórios.</span>
                    <?php endif; ?>
                </p>
                <?php
                $acaoUrl = match ($processo['status']) {
                    'rascunho' => 'submeter',
                    'aguardando_documentos' => 'responder',
                    'rejeitado' => 'reenviar',
                };
                $acaoLabel = match ($processo['status']) {
                    'rascunho' => 'Submeter para verificação',
                    'aguardando_documentos' => 'Responder e reenviar',
                    'rejeitado' => 'Corrigir e reenviar',
                };
                ?>
                <form action="/processos/<?= (int) $processo['id'] ?>/<?= $acaoUrl ?>" method="post" class="mb-2">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button class="btn btn-primary w-100" type="submit" <?= $completos ? '' : 'disabled' ?>>
                        <i class="bi bi-send-check me-1"></i> <?= e($acaoLabel) ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($processo['status'] === 'rascunho'): ?>
                <form action="/processos/<?= (int) $processo['id'] ?>/cancelar" method="post" data-confirm="Cancelar este processo? Esta ação não pode ser desfeita.">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle me-1"></i> Cancelar processo</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isVerificadorAtribuido && $processo['status'] === 'em_verificacao'): ?>
            <button class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalAprovar"><i class="bi bi-check2-circle me-1"></i> Aprovar processo</button>
            <button class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalSolicitar"><i class="bi bi-file-earmark-plus me-1"></i> Solicitar documentos adicionais</button>
            <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#modalRejeitar"><i class="bi bi-x-circle me-1"></i> Rejeitar processo</button>
        <?php endif; ?>

        <?php if ($usuario['perfil'] === 'chefe_setor' && $processo['status'] === 'aprovado_verificador'): ?>
            <form action="/processos/<?= (int) $processo['id'] ?>/aprovar-final" method="post" class="mb-2" data-confirm="Conceder aprovação final a este processo?">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button class="btn btn-success w-100" type="submit"><i class="bi bi-patch-check me-1"></i> Conceder aprovação final</button>
            </form>
            <button class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#modalDevolver"><i class="bi bi-arrow-counterclockwise me-1"></i> Devolver ao verificador</button>
        <?php endif; ?>

        <?php if (in_array($usuario['perfil'], ['chefe_setor', 'administrador'], true) && $processo['status'] === 'rejeitado' && (int) $processo['tentativas_submissao'] >= 3): ?>
            <form action="/processos/<?= (int) $processo['id'] ?>/reatribuir" method="post" class="mb-2">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <label class="form-label small">Reatribuir a verificador</label>
                <select class="form-select form-select-sm mb-2" name="verificador_id" required>
                    <?php foreach ($verificadores as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['nome']) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-primary w-100 btn-sm" type="submit"><i class="bi bi-arrow-repeat me-1"></i> Reatribuir e reabrir verificação</button>
            </form>
        <?php endif; ?>

        <?php if ($usuario['perfil'] === 'administrador' && $processo['status'] === 'aprovado_final'): ?>
            <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#modalReabrir"><i class="bi bi-unlock me-1"></i> Reabrir processo (RN07)</button>
        <?php endif; ?>

        <?php if ($processo['status'] === 'aprovado_final'): ?>
            <div class="alert alert-success py-2 mb-2"><i class="bi bi-patch-check-fill"></i> Processo concluído e aprovado.</div>
        <?php endif; ?>

        <hr>
        <a href="/processos/<?= (int) $processo['id'] ?>/dossie.zip" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-download me-1"></i> Descarregar dossiê (ZIP)</a>
      </div>
    </div>

    <div class="card-soft card">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i>Histórico de Tramitação (RF35)</div>
      <div class="card-body">
        <ul class="timeline">
          <?php foreach ($historico as $h): ?>
            <li>
              <div class="t-what"><?= e($h['acao']) ?></div>
              <div class="t-who"><?= e($h['usuario_nome'] ?? 'Sistema') ?></div>
              <div class="t-when"><?= fmt_datahora($h['data_hora']) ?></div>
              <?php if ($h['observacao']): ?><div class="small text-muted fst-italic">"<?= e($h['observacao']) ?>"</div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php
$modais = [
    ['id' => 'modalAprovar', 'titulo' => 'Parecer técnico', 'acao' => 'aprovar', 'campo' => 'parecer', 'label' => 'Descreva o parecer de aprovação:', 'botao' => 'Confirmar aprovação', 'cor' => 'success'],
    ['id' => 'modalSolicitar', 'titulo' => 'Solicitar documentos adicionais', 'acao' => 'solicitar-docs', 'campo' => 'motivo', 'label' => 'Indique o que é necessário e o prazo (RN04 — 5 dias úteis):', 'botao' => 'Enviar solicitação', 'cor' => 'warning'],
    ['id' => 'modalRejeitar', 'titulo' => 'Rejeitar processo', 'acao' => 'rejeitar', 'campo' => 'motivo', 'label' => 'Especifique os motivos de não conformidade:', 'botao' => 'Confirmar rejeição', 'cor' => 'danger'],
    ['id' => 'modalDevolver', 'titulo' => 'Devolver ao verificador', 'acao' => 'devolver', 'campo' => 'motivo', 'label' => 'Motivo da devolução:', 'botao' => 'Devolver', 'cor' => 'warning'],
    ['id' => 'modalReabrir', 'titulo' => 'Reabrir processo', 'acao' => 'reabrir', 'campo' => 'justificativa', 'label' => 'Justificativa obrigatória para reabrir um processo com aprovação final (RN07):', 'botao' => 'Reabrir processo', 'cor' => 'danger'],
];
foreach ($modais as $m):
?>
<div class="modal fade" id="<?= $m['id'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/processos/<?= (int) $processo['id'] ?>/<?= $m['acao'] ?>" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="modal-header bg-azul text-white">
          <h5 class="modal-title"><?= e($m['titulo']) ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label"><?= e($m['label']) ?></label>
          <textarea class="form-control" name="<?= $m['campo'] ?>" rows="4" <?= $m['campo'] !== 'observacao' ? 'required' : '' ?>></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-<?= $m['cor'] ?>"><?= e($m['botao']) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if ($mostrarRecibo): ?>
<div class="modal fade" id="modalRecibo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-azul text-white">
        <h5 class="modal-title">Recibo de Submissão</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="receipt">SAGDOC — RECIBO DIGITAL DE SUBMISSÃO
--------------------------------------
Protocolo : <b>PROT-<?= (int) date('Y', strtotime($processo['data_submissao'] ?? 'now')) ?>-<?= str_pad((string) $processo['id'], 5, '0', STR_PAD_LEFT) ?></b>
Nº DU     : <?= e($processo['numero_du']) ?>
Importador: <?= e($processo['importador_nome']) ?>
Data/Hora : <?= fmt_datahora($processo['data_submissao']) ?>
Despachante: <?= e($processo['despachante_nome']) ?>
Documentos (<?= count(array_merge(...array_map(fn ($c) => $c['documentos'], $checklist))) ?>):
<?php foreach ($checklist as $c): foreach ($c['documentos'] as $d): ?> • <?= e($d['nome_arquivo']) ?>
<?php endforeach; endforeach; ?>--------------------------------------
Estado: AGUARDANDO DISTRIBUIÇÃO</div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary" data-bs-dismiss="modal">Fechar</button></div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Modal(document.getElementById('modalRecibo')).show();
});
</script>
<?php endif; ?>
