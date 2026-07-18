<?php
use App\Middleware\CsrfMiddleware;
$c = [];
foreach ($config as $row) { $c[$row['chave']] = $row['valor']; }
?>
<div class="page-title">Configuração de SLA</div>
<div class="page-sub">Tempos-alvo por fase do processo (RF44 / RN11) — nunca fixos no código, sempre lidos da base de dados</div>

<div class="card-soft card" style="max-width:560px">
  <div class="card-body">
    <form action="/admin/sla" method="post">
      <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
      <div class="mb-3">
        <label class="form-label">Prazo de distribuição (horas)</label>
        <input type="number" min="1" class="form-control" name="sla_distribuicao_horas" value="<?= e($c['sla_distribuicao_horas']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Prazo de verificação (horas)</label>
        <input type="number" min="1" class="form-control" name="sla_verificacao_horas" value="<?= e($c['sla_verificacao_horas']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Prazo de aprovação do Chefe (horas)</label>
        <input type="number" min="1" class="form-control" name="sla_aprovacao_chefe_horas" value="<?= e($c['sla_aprovacao_chefe_horas']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Tamanho máximo de ficheiro (MB)</label>
        <input type="number" min="1" class="form-control" name="tamanho_max_arquivo_mb" value="<?= e($c['tamanho_max_arquivo_mb']) ?>">
      </div>
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="email_notificacoes" id="email_notif" <?= $c['email_notificacoes'] === 'true' || $c['email_notificacoes'] === '1' ? 'checked' : '' ?>>
        <label class="form-check-label" for="email_notif">Enviar notificações por email</label>
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="modo_manutencao" id="manut" <?= $c['modo_manutencao'] === 'true' || $c['modo_manutencao'] === '1' ? 'checked' : '' ?>>
        <label class="form-check-label" for="manut">Modo de manutenção (bloqueia acesso a não administradores)</label>
      </div>
      <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Guardar configuração</button>
    </form>
  </div>
</div>
