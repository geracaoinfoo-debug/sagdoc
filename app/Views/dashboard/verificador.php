<?php use App\Core\View; ?>
<div class="page-title">Painel do Verificador</div>
<div class="page-sub">Fila de trabalho e desempenho · <?= e($usuario['nome']) ?></div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-inbox', 'cor' => '#12558f', 'numero' => count($fila), 'label' => 'Na fila']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-stopwatch', 'cor' => '#0b3d68', 'numero' => $media . 'h', 'label' => 'Tempo médio verificação']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-exclamation-octagon', 'cor' => '#b02a2a', 'numero' => $atrasados, 'label' => 'SLA ultrapassado']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-check2-all', 'cor' => '#1e7e42', 'numero' => $concluidos, 'label' => 'Processos concluídos']) ?></div>
</div>

<div class="card-soft card">
    <div class="card-header">A minha fila (FIFO por data de submissão)</div>
    <div class="card-body p-0">
        <?= View::partial('tabela_processos', [
            'lista' => $fila,
            'acoes' => fn ($p) => '<a href="/processos/' . (int) $p['id'] . '" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check"></i> Analisar</a>',
        ]) ?>
    </div>
</div>
