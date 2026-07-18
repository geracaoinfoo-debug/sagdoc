<?php
/** @var array $usuarioLogado */
/** @var string $rotaAtual */

$navPorPerfil = [
    'despachante' => [
        ['href' => '/dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Painel'],
        ['href' => '/processos/novo', 'icon' => 'bi-file-earmark-plus', 'label' => 'Novo Processo'],
        ['href' => '/processos', 'icon' => 'bi-folder2-open', 'label' => 'Meus Processos'],
        ['href' => '/consulta', 'icon' => 'bi-search', 'label' => 'Consultar Processos'],
        ['href' => '/notificacoes', 'icon' => 'bi-bell', 'label' => 'Notificações'],
    ],
    'verificador' => [
        ['href' => '/dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Painel'],
        ['href' => '/fila', 'icon' => 'bi-inbox', 'label' => 'Fila de Trabalho'],
        ['href' => '/consulta', 'icon' => 'bi-search', 'label' => 'Consultar Processos'],
        ['href' => '/notificacoes', 'icon' => 'bi-bell', 'label' => 'Notificações'],
    ],
    'chefe_setor' => [
        ['href' => '/dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Painel'],
        ['href' => '/distribuicao', 'icon' => 'bi-diagram-3', 'label' => 'Distribuição'],
        ['href' => '/aprovacao-final', 'icon' => 'bi-check2-circle', 'label' => 'Aprovação Final'],
        ['href' => '/consulta', 'icon' => 'bi-search', 'label' => 'Consultar Processos'],
        ['href' => '/relatorios', 'icon' => 'bi-bar-chart-line', 'label' => 'Relatórios & KPIs'],
        ['href' => '/notificacoes', 'icon' => 'bi-bell', 'label' => 'Notificações'],
    ],
    'gestor' => [
        ['href' => '/dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Painel'],
        ['href' => '/consulta', 'icon' => 'bi-search', 'label' => 'Consultar Processos'],
        ['href' => '/relatorios', 'icon' => 'bi-bar-chart-line', 'label' => 'Relatórios & KPIs'],
        ['href' => '/notificacoes', 'icon' => 'bi-bell', 'label' => 'Notificações'],
    ],
    'administrador' => [
        ['href' => '/dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Painel'],
        ['href' => '/consulta', 'icon' => 'bi-search', 'label' => 'Consultar Processos'],
        ['href' => '/relatorios', 'icon' => 'bi-bar-chart-line', 'label' => 'Relatórios & KPIs'],
        ['href' => '/admin/utilizadores', 'icon' => 'bi-people', 'label' => 'Utilizadores'],
        ['href' => '/admin/tipos-documentos', 'icon' => 'bi-file-earmark-text', 'label' => 'Tipos de Documento'],
        ['href' => '/admin/sla', 'icon' => 'bi-sliders', 'label' => 'Configuração de SLA'],
        ['href' => '/admin/logs', 'icon' => 'bi-shield-check', 'label' => 'Logs de Auditoria'],
        ['href' => '/admin/backup', 'icon' => 'bi-hdd-network', 'label' => 'Backup & Restauro'],
        ['href' => '/notificacoes', 'icon' => 'bi-bell', 'label' => 'Notificações'],
    ],
    'consultor' => [
        ['href' => '/dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Painel'],
        ['href' => '/consulta', 'icon' => 'bi-search', 'label' => 'Consultar Processos'],
        ['href' => '/relatorios', 'icon' => 'bi-bar-chart-line', 'label' => 'Relatórios & KPIs'],
    ],
];

$itens = $navPorPerfil[$usuarioLogado['perfil']] ?? [];
?>
<nav class="sidebar" id="sidebar">
    <div class="who">
        <div class="nome"><?= e($usuarioLogado['nome']) ?></div>
        <div class="perfil"><?= e(perfil_label($usuarioLogado['perfil'])) ?></div>
    </div>
    <div id="navItems">
        <?php foreach ($itens as $item): ?>
            <a href="<?= e($item['href']) ?>" class="nav-item <?= str_starts_with($rotaAtual, $item['href']) && $item['href'] !== '/dashboard' || $rotaAtual === $item['href'] ? 'active' : '' ?>">
                <i class="bi <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
