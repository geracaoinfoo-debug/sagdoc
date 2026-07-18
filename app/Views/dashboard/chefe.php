<?php use App\Core\View; ?>
<div class="page-title">Painel do Chefe de Setor</div>
<div class="page-sub">Distribuição, carga de trabalho e aprovações · <?= e($usuario['nome']) ?></div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-diagram-3', 'cor' => '#12558f', 'numero' => count($aguardDist), 'label' => 'Aguardam distribuição']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-check2-circle', 'cor' => '#b8860b', 'numero' => count($aguardAprov), 'label' => 'Aguardam aprovação final']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-people', 'cor' => '#0b3d68', 'numero' => count($verificadores), 'label' => 'Verificadores']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-files', 'cor' => '#1e7e42', 'numero' => $totalProcessos, 'label' => 'Processos totais']) ?></div>
</div>

<div class="card-soft card mb-4">
    <div class="card-header">Carga de trabalho por verificador</div>
    <div class="card-body p-0">
        <table class="table tbl mb-0">
            <thead><tr><th>Verificador</th><th>Matrícula</th><th>Processos em verificação</th></tr></thead>
            <tbody>
            <?php foreach ($verificadores as $v): ?>
                <tr>
                    <td><?= e($v['nome']) ?></td>
                    <td><?= e($v['matricula']) ?></td>
                    <td><div class="progress" style="height:16px"><div class="progress-bar bg-azul" style="width:<?= min((int) $v['carga'] * 20, 100) ?>%"><?= (int) $v['carga'] ?></div></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft card">
    <div class="card-header">Processos a aguardar distribuição</div>
    <div class="card-body p-0">
        <?= View::partial('tabela_processos', [
            'lista' => $aguardDist,
            'acoes' => fn ($p) => '<a href="/distribuicao" class="btn btn-sm btn-primary"><i class="bi bi-arrow-right"></i> Distribuir</a>',
        ]) ?>
    </div>
</div>
