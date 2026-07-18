<?php

use App\Services\SLAService;

/** @var array $lista */
/** @var \Closure|null $acoes */
$acoes = $acoes ?? null;
?>
<?php if (!$lista): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-folder2 fs-1 d-block mb-2 opacity-50"></i>Sem processos para apresentar.
    </div>
<?php else: ?>
<div class="table-responsive">
<table class="table tbl align-middle mb-0">
    <thead>
    <tr>
        <th>Nº DU</th><th>Importador</th><th>Categoria</th>
        <th class="d-none d-lg-table-cell">Despachante</th><th>Estado</th>
        <th class="d-none d-md-table-cell">SLA</th><th class="text-end">Ações</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($lista as $p): $sla = SLAService::status($p); ?>
        <tr>
            <td><b><?= e($p['numero_du']) ?></b></td>
            <td><?= e($p['importador_nome'] ?? '—') ?></td>
            <td><?= e($p['categoria']) ?></td>
            <td class="d-none d-lg-table-cell"><?= e($p['despachante_nome'] ?? '—') ?></td>
            <td><span class="badge-st st-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
            <td class="d-none d-md-table-cell"><span class="sla-dot sla-<?= e($sla['cor']) ?>"></span><small><?= e($sla['label']) ?></small></td>
            <td class="text-end">
                <?php if ($acoes): ?>
                    <?= $acoes($p) ?>
                <?php else: ?>
                    <a href="/processos/<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
