<?php

declare(strict_types=1);

namespace App\Models;

final class Importador extends BaseModel
{
    public static function todos(): array
    {
        return self::db()->query('SELECT * FROM importadores ORDER BY nome')->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM importadores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function porNif(string $nif): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM importadores WHERE nif = :nif');
        $stmt->execute(['nif' => $nif]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(string $nome, string $nif, ?string $endereco = null, ?string $telefone = null, ?string $email = null): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO importadores (nome, nif, endereco, telefone, email) VALUES (:nome, :nif, :end, :tel, :email)'
        );
        $stmt->execute(['nome' => $nome, 'nif' => $nif, 'end' => $endereco, 'tel' => $telefone, 'email' => $email]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * RN10 — 100% conforme (sem rejeições) nos últimos 12 meses considera-se operador confiável.
     */
    public static function ehOperadorConfiavel(int $importadorId): bool
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) AS total, SUM(status = 'rejeitado') AS rejeitados
             FROM processos_documentais
             WHERE importador_id = :id AND data_criacao > (NOW() - INTERVAL 12 MONTH)"
        );
        $stmt->execute(['id' => $importadorId]);
        $row = $stmt->fetch();
        return $row && (int) $row['total'] > 0 && (int) $row['rejeitados'] === 0;
    }
}
