<div class="page-title">Notificações</div>
<div class="page-sub">Eventos relevantes dos seus processos (RF30)</div>

<div class="card-soft card">
  <div class="card-body">
    <?php if (!$lista): ?>
      <p class="text-muted text-center py-4">Sem notificações.</p>
    <?php endif; ?>
    <?php foreach ($lista as $n): ?>
      <a href="<?= e($n['link_referencia'] ?? '#') ?>" class="d-flex gap-2 py-2 border-bottom text-decoration-none <?= $n['lida'] ? 'opacity-50' : '' ?>">
        <i class="bi bi-bell<?= $n['lida'] ? '' : '-fill' ?> text-primary"></i>
        <div>
          <div class="fw-semibold text-dark"><?= e($n['titulo']) ?></div>
          <div class="small text-muted"><?= e($n['mensagem']) ?> · <?= fmt_datahora($n['data_hora']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
