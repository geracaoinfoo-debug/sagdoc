<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * RF03/§8 — matriz de permissões. `roles` no registo da rota lista os perfis
 * autorizados; ausência de `roles` significa "qualquer utilizador autenticado".
 */
final class RbacMiddleware
{
    /**
     * Predicado puro (sem I/O) — extraído para ser testável isoladamente (§18).
     */
    public static function permitido(?string $perfil, array $roles): bool
    {
        if ($roles === []) {
            return true;
        }
        return $perfil !== null && in_array($perfil, $roles, true);
    }

    public static function handle(Request $request, array $roles): void
    {
        $usuario = AuthMiddleware::usuario();

        if (!self::permitido($usuario['perfil'] ?? null, $roles)) {
            if ($request->wantsJson()) {
                Response::json(['erro' => 'Acesso negado para o seu perfil.'], 403);
            }
            Response::abort(403, 'Acesso negado: o seu perfil não tem permissão para aceder a este recurso.');
        }
    }
}
