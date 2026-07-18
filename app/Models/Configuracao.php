<?php

declare(strict_types=1);

namespace App\Models;

final class Configuracao extends BaseModel
{
    /** @var array<string, mixed>|null cache curto por request */
    private static ?array $cache = null;

    private static function carregar(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            $stmt = self::db()->query('SELECT chave, valor, tipo FROM configuracoes');
            foreach ($stmt->fetchAll() as $row) {
                self::$cache[$row['chave']] = self::converter($row['valor'], $row['tipo']);
            }
        }
        return self::$cache;
    }

    private static function converter(string $valor, string $tipo): mixed
    {
        return match ($tipo) {
            'int' => (int) $valor,
            'bool' => in_array(strtolower($valor), ['1', 'true'], true),
            'json' => json_decode($valor, true),
            default => $valor,
        };
    }

    public static function get(string $chave, mixed $default = null): mixed
    {
        $todas = self::carregar();
        return $todas[$chave] ?? $default;
    }

    public static function todas(): array
    {
        $stmt = self::db()->query('SELECT * FROM configuracoes ORDER BY chave');
        return $stmt->fetchAll();
    }

    public static function definir(string $chave, string $valor): void
    {
        self::db()->prepare('UPDATE configuracoes SET valor = :v WHERE chave = :k')
            ->execute(['v' => $valor, 'k' => $chave]);
        self::$cache = null;
    }
}
