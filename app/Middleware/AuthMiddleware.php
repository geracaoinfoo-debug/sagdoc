<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public static function usuario(): ?array
    {
        return Session::get('usuario');
    }

    public static function handle(Request $request): void
    {
        if (self::usuario() === null) {
            if ($request->wantsJson()) {
                Response::json(['erro' => 'Sessão expirada. Autentique-se novamente.'], 401);
            }
            Session::set('_redirect_after_login', $request->path);
            Response::redirect('/login');
        }
    }
}
