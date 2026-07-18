<?php use App\Core\View; ?>
<div class="page-title">Fila de Trabalho</div>
<div class="page-sub">Processos atribuídos a si, por ordem de submissão (FIFO) — operadores confiáveis têm prioridade (RN10)</div>

<div class="card-soft card">
  <div class="card-body p-0">
    <?= View::partial('tabela_processos', [
        'lista' => $lista,
        'acoes' => fn ($p) => '<a href="/processos/' . (int) $p['id'] . '" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check"></i> Analisar</a>',
    ]) ?>
  </div>
</div>
