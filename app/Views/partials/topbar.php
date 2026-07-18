<?php
/** @var array $usuarioLogado */
use App\Models\Notificacao;

$naoLidas = Notificacao::naoLidas((int) $usuarioLogado['id']);
?>
<div class="topbar">
    <button class="btn btn-sm text-white d-md-none me-2" data-toggle="sidebar"><i class="bi bi-list fs-4"></i></button>
    <a href="/dashboard" class="logo">SAGDOC</a>
    <span class="dga-strip"></span>
    <span class="d-none d-md-inline" style="font-size:13px;opacity:.85">Gestão Documental Aduaneira · DGA</span>
    <div class="ms-auto d-flex align-items-center gap-3">
        <div class="position-relative" role="button" id="notifBell" data-bs-toggle="offcanvas" data-bs-target="#notifPanel" title="Notificações">
            <i class="bi bi-bell fs-5 text-white"></i>
            <?php if ($naoLidas > 0): ?>
                <span class="notif-badge"><?= (int) $naoLidas ?></span>
            <?php endif; ?>
        </div>
        <div class="dropdown">
            <span role="button" data-bs-toggle="dropdown" class="d-flex align-items-center gap-2 text-white">
                <i class="bi bi-person-circle fs-5"></i>
                <span class="d-none d-md-inline" style="font-size:14px"><?= e(explode(' ', $usuarioLogado['nome'])[0]) ?></span>
                <i class="bi bi-caret-down-fill" style="font-size:11px"></i>
            </span>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?= e(perfil_label($usuarioLogado['perfil'])) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/notificacoes"><i class="bi bi-bell me-2"></i>Notificações</a></li>
                <li>
                    <form action="/logout" method="post">
                        <input type="hidden" name="_csrf" value="<?= e(\App\Middleware\CsrfMiddleware::token()) ?>">
                        <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Terminar sessão</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="notifPanel">
    <div class="offcanvas-header bg-azul text-white">
        <h5 class="offcanvas-title"><i class="bi bi-bell me-2"></i>Notificações</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="notifList">
        <p class="text-muted text-center py-4">A carregar…</p>
    </div>
</div>
