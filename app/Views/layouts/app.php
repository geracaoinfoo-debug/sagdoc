<?php

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

$usuarioLogado = AuthMiddleware::usuario();
$rotaAtual = $_SERVER['REQUEST_URI'] ?? '/';
$rotaAtual = parse_url($rotaAtual, PHP_URL_PATH) ?? '/';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(CsrfMiddleware::token()) ?>">
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' — SAGDOC' : 'SAGDOC — Sistema de Apoio à Gestão Documental' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/sagdoc.css" rel="stylesheet">
</head>
<body>

<div id="view-app">
    <?= View::partial('topbar', ['usuarioLogado' => $usuarioLogado]) ?>
    <?= View::partial('sidebar', ['usuarioLogado' => $usuarioLogado, 'rotaAtual' => $rotaAtual]) ?>
    <main class="content" id="content">
        <?= View::partial('flash') ?>
        <?= $content ?>
    </main>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.0.379/build/pdf.min.js"></script>
<script src="/assets/js/sagdoc.js"></script>
<?php if (isset($scripts)) { echo $scripts; } ?>
</body>
</html>
