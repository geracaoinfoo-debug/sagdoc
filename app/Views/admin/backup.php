<?php use App\Middleware\CsrfMiddleware; ?>
<div class="page-title">Backup &amp; Restauro</div>
<div class="page-sub">Backups da base de dados (RF47) — armazenados em storage/backups, fora do webroot público</div>

<div class="card-soft card mb-4" style="max-width:560px">
  <div class="card-body">
    <form action="/admin/backup/gerar" method="post">
      <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
      <button class="btn btn-primary" type="submit"><i class="bi bi-hdd-network me-1"></i> Gerar novo backup agora</button>
    </form>
    <p class="small text-muted mt-2 mb-0">
      Recomenda-se agendar esta operação diariamente via Agendador de Tarefas do Windows
      (<code>bin/backup_diario.php</code>), com cópia para localização geograficamente separada (RNF15).
    </p>
  </div>
</div>

<div class="card-soft card">
  <div class="card-header">Backups existentes</div>
  <div class="card-body p-0">
    <?php if (!$backups): ?>
      <div class="text-center text-muted py-4">Ainda não existem backups.</div>
    <?php else: ?>
      <table class="table tbl mb-0">
        <thead><tr><th>Ficheiro</th><th>Tamanho</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
          <tr>
            <td><?= e($b['nome']) ?></td>
            <td><?= fmt_bytes($b['tamanho']) ?></td>
            <td><?= fmt_datahora($b['data']) ?></td>
            <td class="text-end"><a href="/admin/backup/download/<?= e($b['nome']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
