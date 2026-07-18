<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(\App\Middleware\CsrfMiddleware::token()) ?>">
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' — SAGDOC' : 'SAGDOC — Sistema de Apoio à Gestão Documental' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/sagdoc.css" rel="stylesheet">
</head>
<body>
<?= $content ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/sagdoc.js"></script>
</body>
</html>
