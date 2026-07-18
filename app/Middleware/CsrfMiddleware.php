<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class CsrfMiddleware
{
    public static function token(): string
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return (string) Session::get('csrf_token');
    }

    public static function handle(Request $request): void
    {
        if (!in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $sent = $request->input('_csrf', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = Session::get('csrf_token', '');

        if (!$sent || !$expected || !hash_equals((string) $expected, (string) $sent)) {
            if ($request->wantsJson()) {
                Response::json(['erro' => 'Token CSRF inválido ou expirado.'], 419);
            }
            Response::abort(419, 'Token CSRF inválido ou expirado. Volte atrás e tente novamente.');
        }
    }
}
