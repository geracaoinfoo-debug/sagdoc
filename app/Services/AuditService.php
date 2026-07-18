<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\LogAuditoria;

final class AuditService
{
    public static function log(string $acao, ?string $entidade = null, ?int $idEntidade = null, ?string $detalhe = null): void
    {
        $usuario = Session::get('usuario');
        LogAuditoria::registar(
            $usuario['id'] ?? null,
            $acao,
            $entidade,
            $idEntidade,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $detalhe !== null ? ['detalhe' => $detalhe] : null
        );
    }
}
