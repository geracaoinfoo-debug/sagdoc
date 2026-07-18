<div class="page-title">Logs de Auditoria</div>
<div class="page-sub">Registo imutável de operações sensíveis (RF05/RF46/RN12)</div>

<div class="card-soft card mb-3">
  <div class="card-body">
    <form method="get" action="/admin/logs" class="row g-2">
      <div class="col-md-4"><input class="form-control form-control-sm" name="acao" placeholder="Filtrar por ação (ex: LOGIN, PROC_)" value="<?= e($filtros['acao'] ?? '') ?>"></div>
      <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit">Filtrar</button></div>
    </form>
  </div>
</div>

<div class="card-soft card">
  <div class="card-body p-0">
    <table class="table tbl mb-0" style="font-size:12.5px">
      <thead><tr><th>Data/Hora</th><th>Utilizador</th><th>Ação</th><th>Entidade</th><th>IP</th><th>Detalhes</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $l): $det = $l['detalhes'] ? json_decode($l['detalhes'], true) : null; ?>
        <tr>
          <td><?= fmt_datahora($l['data_hora']) ?></td>
          <td><?= e($l['usuario_nome'] ?? '—') ?></td>
          <td><code><?= e($l['acao']) ?></code></td>
          <td><?= e(($l['entidade_afetada'] ?? '') . ' ' . ($l['id_entidade'] ?? '')) ?></td>
          <td><?= e($l['ip_origem'] ?? '') ?></td>
          <td class="text-muted"><?= e($det['detalhe'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
