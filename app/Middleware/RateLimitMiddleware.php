<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * RNF13/§16 — 5 tentativas / 15 min por IP+utilizador no login.
 */
final class RateLimitMiddleware
{
    private const MAX_TENTATIVAS = 5;
    private const JANELA_MINUTOS = 15;

    public static function verificar(string $username, string $ip): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE username = :u AND ip = :ip AND sucesso = 0
               AND data_hora > (NOW() - INTERVAL :janela MINUTE)'
        );
        $stmt->execute(['u' => $username, 'ip' => $ip, 'janela' => self::JANELA_MINUTOS]);

        if ((int) $stmt->fetchColumn() >= self::MAX_TENTATIVAS) {
            Response::abort(429, 'Demasiadas tentativas falhadas. Tente novamente dentro de ' . self::JANELA_MINUTOS . ' minutos.');
        }
    }

    public static function registar(string $username, string $ip, bool $sucesso): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (username, ip, sucesso, data_hora) VALUES (:u, :ip, :s, NOW())'
        );
        $stmt->execute(['u' => $username, 'ip' => $ip, 's' => $sucesso ? 1 : 0]);
    }
}
