<?php use App\Core\View; ?>
<div class="page-title">Relatórios &amp; KPIs</div>
<div class="page-sub">Indicadores de desempenho e exportação de relatórios (RF40/RF41)</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-stopwatch', 'cor' => '#0b3d68', 'numero' => $kpis['tempo_medio_horas'] . 'h', 'label' => 'Tempo médio tramitação']) ?></div>
  <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-speedometer', 'cor' => '#1e7e42', 'numero' => $kpis['percentual_dentro_sla'] . '%', 'label' => 'Dentro do SLA']) ?></div>
  <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-arrow-repeat', 'cor' => '#b8860b', 'numero' => $kpis['total_retrabalho'], 'label' => 'Retrabalho / reenvios']) ?></div>
  <div class="col-6 col-lg-3"><?= View::partial('stat_card', ['icon' => 'bi-files', 'cor' => '#12558f', 'numero' => $kpis['total_processos'], 'label' => 'Processos totais']) ?></div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card-soft card h-100">
      <div class="card-header d-flex justify-content-between">
        Relatório por despachante
        <a href="/relatorios/export?tipo=despachantes" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> CSV</a>
      </div>
      <div class="card-body p-0">
        <table class="table tbl mb-0">
          <thead><tr><th>Despachante</th><th>Total</th><th>Aprovados</th><th>Rejeitados</th></tr></thead>
          <tbody>
          <?php foreach ($porDespachante as $r): ?>
            <tr><td><?= e($r['nome']) ?></td><td><?= (int) $r['total'] ?></td><td><?= (int) $r['aprovados'] ?></td><td><?= (int) $r['rejeitados'] ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-soft card h-100">
      <div class="card-header d-flex justify-content-between">
        Desempenho de verificadores
        <a href="/relatorios/export?tipo=verificadores" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> CSV</a>
      </div>
      <div class="card-body p-0">
        <table class="table tbl mb-0">
          <thead><tr><th>Verificador</th><th>Processos</th><th>Tempo médio</th></tr></thead>
          <tbody>
          <?php foreach ($porVerificador as $r): ?>
            <tr><td><?= e($r['verificador_nome']) ?></td><td><?= (int) $r['total_processos'] ?></td><td><?= $r['tempo_medio_horas'] !== null ? round((float) $r['tempo_medio_horas'], 1) . 'h' : '—' ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="mt-3">
  <a href="/relatorios/export?tipo=processos" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i> Exportar todos os processos (CSV)</a>
  <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir / Exportar PDF</button>
</div>
