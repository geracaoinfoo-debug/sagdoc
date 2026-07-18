<?php

declare(strict_types=1);

namespace App\Models;

final class Documento extends BaseModel
{
    public static function porProcesso(int $processoId): array
    {
        $stmt = self::db()->prepare(
            'SELECT d.*, t.nome AS tipo_nome
               FROM documentos d
               INNER JOIN tipos_documentos t ON t.id = d.tipo_documento_id
              WHERE d.processo_id = :pid
              ORDER BY d.data_upload ASC'
        );
        $stmt->execute(['pid' => $processoId]);
        return $stmt->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT d.*, t.nome AS tipo_nome
               FROM documentos d
               INNER JOIN tipos_documentos t ON t.id = d.tipo_documento_id
              WHERE d.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO documentos
                (processo_id, tipo_documento_id, nome_arquivo, caminho_arquivo, tamanho_bytes, hash_sha256, data_validade, upload_por)
             VALUES (:pid, :tid, :nome, :caminho, :tamanho, :hash, :validade, :upload_por)'
        );
        $stmt->execute([
            'pid' => $dados['processo_id'],
            'tid' => $dados['tipo_documento_id'],
            'nome' => $dados['nome_arquivo'],
            'caminho' => $dados['caminho_arquivo'],
            'tamanho' => $dados['tamanho_bytes'],
            'hash' => $dados['hash_sha256'],
            'validade' => $dados['data_validade'] ?? null,
            'upload_por' => $dados['upload_por'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function remover(int $id): void
    {
        self::db()->prepare('DELETE FROM documentos WHERE id = :id')->execute(['id' => $id]);
    }

    public static function marcarVerificado(int $id, bool $verificado): void
    {
        self::db()->prepare('UPDATE documentos SET verificado = :v WHERE id = :id')
            ->execute(['v' => $verificado ? 1 : 0, 'id' => $id]);
    }

    /**
     * RN08 — documentos com validade próxima do vencimento (30 dias) ou já expirados.
     */
    public static function proximosDoVencimento(int $usuarioId, string $perfil): array
    {
        $sql = "SELECT d.*, t.nome AS tipo_nome, p.numero_du, p.id AS processo_id
                  FROM documentos d
                  INNER JOIN tipos_documentos t ON t.id = d.tipo_documento_id
                  INNER JOIN processos_documentais p ON p.id = d.processo_id
                 WHERE d.data_validade IS NOT NULL
                   AND d.data_validade <= (CURDATE() + INTERVAL 30 DAY)";
        $params = [];
        if ($perfil === 'despachante') {
            $sql .= ' AND p.despachante_id = :uid';
            $params['uid'] = $usuarioId;
        }
        $sql .= ' ORDER BY d.data_validade ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RN09 — documentos de validade estendida do mesmo importador, reutilizáveis sem reupload.
     */
    public static function reutilizaveisParaImportador(int $importadorId, int $tipoDocumentoId): array
    {
        $stmt = self::db()->prepare(
            "SELECT d.*, t.nome AS tipo_nome
               FROM documentos d
               INNER JOIN tipos_documentos t ON t.id = d.tipo_documento_id
               INNER JOIN processos_documentais p ON p.id = d.processo_id
              WHERE p.importador_id = :imp
                AND d.tipo_documento_id = :tipo
                AND (d.data_validade IS NULL OR d.data_validade >= CURDATE())
              ORDER BY d.data_upload DESC"
        );
        $stmt->execute(['imp' => $importadorId, 'tipo' => $tipoDocumentoId]);
        return $stmt->fetchAll();
    }
}
