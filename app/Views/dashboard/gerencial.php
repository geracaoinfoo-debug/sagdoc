<?php use App\Core\View; ?>
<div class="page-title">Painel Gerencial (DGA)</div>
<div class="page-sub">Indicadores globais de tramitação documental</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-files', 'cor' => '#12558f', 'numero' => $kpis['total_processos'], 'label' => 'Volume total']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-stopwatch', 'cor' => '#0b3d68', 'numero' => $kpis['tempo_medio_horas'] . 'h', 'label' => 'Tempo médio tramitação']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-hand-thumbs-up', 'cor' => '#1e7e42', 'numero' => $kpis['taxa_aprovacao'] . '%', 'label' => 'Taxa de aprovação']) ?></div>
    <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-hand-thumbs-down', 'cor' => '#b02a2a', 'numero' => $kpis['total_rejeicoes'], 'label' => 'Rejeições']) ?></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-soft card h-100">
            <div class="card-header">Processos por estado</div>
            <div class="card-body"><canvas id="chStatus" height="150"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-soft card h-100">
            <div class="card-header">Aprovação vs. Rejeição</div>
            <div class="card-body"><canvas id="chAprov" height="150"></canvas></div>
        </div>
    </div>
</div>

<?php
$labels = array_map('status_label', array_keys($porStatus));
$valores = array_values($porStatus);
$aprovados = $porStatus['aprovado_final'] ?? 0;
$rejeitados = $porStatus['rejeitado'] ?? 0;
$emCurso = max(0, $kpis['total_processos'] - $aprovados - $rejeitados);
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('chStatus'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{ data: <?= json_encode($valores) ?>, backgroundColor: '#12558f' }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    new Chart(document.getElementById('chAprov'), {
        type: 'doughnut',
        data: {
            labels: ['Aprovados', 'Rejeitados', 'Em curso'],
            datasets: [{ data: [<?= (int) $aprovados ?>, <?= (int) $rejeitados ?>, <?= (int) $emCurso ?>], backgroundColor: ['#1e7e42', '#b02a2a', '#c8a34a'] }]
        }
    });
});
</script>
