<?php

declare(strict_types=1);

namespace App\Models;

final class Notificacao extends BaseModel
{
    public static function porUsuario(int $usuarioId, int $limite = 50): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :uid ORDER BY data_hora DESC LIMIT :lim'
        );
        $stmt->bindValue('uid', $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function naoLidas(int $usuarioId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid AND lida = 0');
        $stmt->execute(['uid' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public static function criar(int $usuarioId, string $tipo, string $titulo, string $mensagem, ?string $link = null): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link_referencia)
             VALUES (:uid, :tipo, :titulo, :msg, :link)'
        );
        $stmt->execute(['uid' => $usuarioId, 'tipo' => $tipo, 'titulo' => $titulo, 'msg' => $mensagem, 'link' => $link]);
        return (int) $pdo->lastInsertId();
    }

    public static function marcarLida(int $id, int $usuarioId): void
    {
        self::db()->prepare('UPDATE notificacoes SET lida = 1 WHERE id = :id AND usuario_id = :uid')
            ->execute(['id' => $id, 'uid' => $usuarioId]);
    }

    public static function marcarTodasLidas(int $usuarioId): void
    {
        self::db()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = :uid')->execute(['uid' => $usuarioId]);
    }

    public static function porId(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM notificacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Evita reenviar o mesmo alerta (ex.: SLA ultrapassado) repetidamente a
     * cada execução do job periódico — só volta a notificar se a última
     * ocorrência for anterior a $desde (ex.: o início da fase atual).
     */
    public static function existeDesde(string $tipo, string $link, string $desde): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM notificacoes WHERE tipo = :tipo AND link_referencia = :link AND data_hora >= :desde'
        );
        $stmt->execute(['tipo' => $tipo, 'link' => $link, 'desde' => $desde]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
