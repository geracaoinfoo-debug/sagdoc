<?php

declare(strict_types=1);

namespace App\Models;

final class Comunicacao extends BaseModel
{
    public static function porProcesso(int $processoId): array
    {
        $stmt = self::db()->prepare(
            'SELECT c.*, u.nome AS remetente_nome
               FROM comunicacoes c
               INNER JOIN usuarios u ON u.id = c.remetente_id
              WHERE c.processo_id = :pid
              ORDER BY c.data_hora ASC'
        );
        $stmt->execute(['pid' => $processoId]);
        return $stmt->fetchAll();
    }

    public static function enviar(int $processoId, int $remetenteId, int $destinatarioId, string $mensagem, ?string $assunto = null): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO comunicacoes (processo_id, remetente_id, destinatario_id, assunto, mensagem)
             VALUES (:pid, :rem, :dest, :assunto, :msg)'
        );
        $stmt->execute([
            'pid' => $processoId,
            'rem' => $remetenteId,
            'dest' => $destinatarioId,
            'assunto' => $assunto,
            'msg' => $mensagem,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function marcarLidas(int $processoId, int $usuarioId): void
    {
        self::db()->prepare('UPDATE comunicacoes SET lida = 1 WHERE processo_id = :pid AND destinatario_id = :uid')
            ->execute(['pid' => $processoId, 'uid' => $usuarioId]);
    }
}
