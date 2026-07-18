<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Notificacao;

final class NotificacaoController
{
    public function index(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        echo View::render('notificacoes/index', [
            'tituloPagina' => 'Notificações',
            'lista' => Notificacao::porUsuario((int) $usuario['id']),
        ]);
    }

    public function listaJson(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        $lista = Notificacao::porUsuario((int) $usuario['id']);
        foreach ($lista as &$n) {
            $n['data_hora_fmt'] = fmt_datahora($n['data_hora']);
        }
        Response::json($lista);
    }

    public function marcarLida(Request $request, array $params): void
    {
        $usuario = AuthMiddleware::usuario();
        Notificacao::marcarLida((int) $params['id'], (int) $usuario['id']);
        if ($request->wantsJson()) {
            Response::json(['ok' => true]);
        }
        Response::redirect('/notificacoes');
    }
}
