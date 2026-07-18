<?php use App\Core\View; ?>
<div class="page-title">Aprovação Final</div>
<div class="page-sub">Reveja os pareceres dos verificadores e conceda a aprovação final</div>

<div class="card-soft card">
  <div class="card-body p-0">
    <?= View::partial('tabela_processos', [
        'lista' => $lista,
        'acoes' => fn ($p) => '<a href="/processos/' . (int) $p['id'] . '" class="btn btn-sm btn-success"><i class="bi bi-check2-circle"></i> Rever</a>',
    ]) ?>
  </div>
</div>
