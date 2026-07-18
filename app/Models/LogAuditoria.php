<?php

declare(strict_types=1);

namespace App\Models;

/**
 * RN12 — tabela imutável: este modelo expõe apenas INSERT e SELECT, nunca UPDATE/DELETE.
 */
final class LogAuditoria extends BaseModel
{
    public static function registar(?int $usuarioId, string $acao, ?string $entidade, ?int $idEntidade, string $ip, string $userAgent, ?array $detalhes = null): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO logs_auditoria (usuario_id, acao, entidade_afetada, id_entidade, ip_origem, user_agent, detalhes)
             VALUES (:uid, :acao, :ent, :idEnt, :ip, :ua, :det)'
        );
        $stmt->execute([
            'uid' => $usuarioId,
            'acao' => $acao,
            'ent' => $entidade,
            'idEnt' => $idEntidade,
            'ip' => $ip,
            'ua' => mb_substr($userAgent, 0, 500),
            'det' => $detalhes !== null ? json_encode($detalhes, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function recentes(int $limite = 200, array $filtros = []): array
    {
        $sql = 'SELECT l.*, u.nome AS usuario_nome FROM logs_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE 1=1';
        $params = [];
        if (!empty($filtros['acao'])) {
            $sql .= ' AND l.acao LIKE :acao';
            $params['acao'] = '%' . $filtros['acao'] . '%';
        }
        if (!empty($filtros['usuario_id'])) {
            $sql .= ' AND l.usuario_id = :uid';
            $params['uid'] = $filtros['usuario_id'];
        }
        $sql .= ' ORDER BY l.data_hora DESC LIMIT ' . max(1, min(1000, $limite));

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
