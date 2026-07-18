<?php use App\Core\View; ?>
<div class="page-title">Painel do Despachante</div>
<div class="page-sub">Resumo dos seus processos documentais · <?= e($usuario['nome']) ?></div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-folder2-open', 'cor' => '#12558f', 'numero' => $ativos, 'label' => 'Processos ativos']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-exclamation-triangle', 'cor' => '#b8860b', 'numero' => $aguardando, 'label' => 'Aguardam a sua ação']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-check2-circle', 'cor' => '#1e7e42', 'numero' => $aprovados, 'label' => 'Aprovados']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-files', 'cor' => '#0b3d68', 'numero' => $total, 'label' => 'Total submetidos']) ?></div>
</div>

<?php if ($aguardando > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-bell-fill"></i>
    <div>Tem <b><?= (int) $aguardando ?></b> processo(s) que aguardam a sua ação (documentos adicionais ou correções).</div>
</div>
<?php endif; ?>

<div class="card-soft card">
    <div class="card-header d-flex justify-content-between align-items-center">
        Processos recentes
        <a href="/processos/novo" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Novo Processo</a>
    </div>
    <div class="card-body p-0"><?= View::partial('tabela_processos', ['lista' => $recentes]) ?></div>
</div>
