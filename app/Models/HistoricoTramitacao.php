<?php

declare(strict_types=1);

namespace App\Models;

final class HistoricoTramitacao extends BaseModel
{
    public static function porProcesso(int $processoId): array
    {
        $stmt = self::db()->prepare(
            'SELECT h.*, u.nome AS usuario_nome
               FROM historico_tramitacao h
               LEFT JOIN usuarios u ON u.id = h.usuario_id
              WHERE h.processo_id = :pid
              ORDER BY h.data_hora DESC, h.id DESC'
        );
        $stmt->execute(['pid' => $processoId]);
        return $stmt->fetchAll();
    }

    public static function registar(int $processoId, ?int $usuarioId, string $acao, ?string $statusAnterior, ?string $statusNovo, ?string $observacao = null): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO historico_tramitacao (processo_id, usuario_id, acao, status_anterior, status_novo, observacao)
             VALUES (:pid, :uid, :acao, :ant, :novo, :obs)'
        );
        $stmt->execute([
            'pid' => $processoId,
            'uid' => $usuarioId,
            'acao' => $acao,
            'ant' => $statusAnterior,
            'novo' => $statusNovo,
            'obs' => $observacao,
        ]);
        return (int) $pdo->lastInsertId();
    }
}
