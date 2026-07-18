<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RbacMiddleware;
use App\Services\AuditService;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);
Env::load($basePath . '/.env');
View::init($basePath . '/app/Views');

$appConfig = require $basePath . '/config/app.php';
if ($appConfig['env'] === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

set_exception_handler(function (Throwable $e) use ($appConfig): void {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    if ($appConfig['env'] !== 'production') {
        Response::abort(500, $e->getMessage());
    }
    Response::abort(500, 'Ocorreu um erro interno. A equipa técnica foi notificada.');
});

Session::start();

$router = new Router();
(require $basePath . '/app/routes.php')($router);

$request = new Request();
$match = $router->match($request->method, $request->path);

if ($match === null) {
    Response::abort(404, 'Página não encontrada.');
}

$opts = $match['opts'];

// Modo de manutenção (RF44/§12) — exceto para administradores.
if ($request->path !== '/login' && $request->path !== '/logout') {
    try {
        $stmt = Database::connection()->prepare("SELECT valor FROM configuracoes WHERE chave = 'modo_manutencao'");
        $stmt->execute();
        $manutencao = $stmt->fetchColumn();
        $usuarioAtual = AuthMiddleware::usuario();
        if ($manutencao === '1' && ($usuarioAtual['perfil'] ?? null) !== 'administrador') {
            Response::abort(503, 'SAGDOC em manutenção. Tente novamente mais tarde.');
        }
    } catch (\Throwable) {
        // BD ainda não inicializada (ex.: antes do schema ser carregado) — ignora.
    }
}

if (($opts['auth'] ?? true) === true) {
    AuthMiddleware::handle($request);
}

if (!empty($opts['roles'])) {
    RbacMiddleware::handle($request, $opts['roles']);
}

if (($opts['csrf'] ?? true) === true) {
    CsrfMiddleware::handle($request);
}

[$controllerClass, $method] = $match['handler'];
$controller = new $controllerClass();

try {
    $controller->$method($request, $match['params']);
} catch (\PDOException $e) {
    error_log('[DB] ' . $e->getMessage());
    AuditService::log('ERRO_BD', 'sistema', null, $e->getMessage());
    Response::abort(500, 'Erro ao aceder à base de dados.');
}
