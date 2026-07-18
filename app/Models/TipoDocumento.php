<?php

declare(strict_types=1);

namespace App\Models;

final class TipoDocumento extends BaseModel
{
    public static function todos(bool $apenasAtivos = false): array
    {
        $sql = 'SELECT * FROM tipos_documentos' . ($apenasAtivos ? ' WHERE ativo = 1' : '') . ' ORDER BY nome';
        return self::db()->query($sql)->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM tipos_documentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * RN02 — checklist dinâmico: obrigatório se obrigatorio_para contém "*" ou a categoria dada.
     */
    public static function checklistParaCategoria(string $categoria): array
    {
        $tipos = self::todos(true);
        $out = [];
        foreach ($tipos as $tipo) {
            $obrigatorioPara = json_decode($tipo['obrigatorio_para'], true) ?? [];
            $obrigatorio = in_array('*', $obrigatorioPara, true) || in_array($categoria, $obrigatorioPara, true);
            $out[] = [
                'id' => (int) $tipo['id'],
                'nome' => $tipo['nome'],
                'obrigatorio' => $obrigatorio,
                'validade_meses' => $tipo['validade_meses'],
            ];
        }
        return $out;
    }

    public static function criar(array $dados): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO tipos_documentos (nome, descricao, formatos_aceites, obrigatorio_para, validade_meses, ativo)
             VALUES (:nome, :desc, :formatos, :obrig, :validade, :ativo)'
        );
        $stmt->execute([
            'nome' => $dados['nome'],
            'desc' => $dados['descricao'] ?? null,
            'formatos' => json_encode($dados['formatos_aceites'] ?? ['pdf', 'jpg', 'png']),
            'obrig' => json_encode($dados['obrigatorio_para'] ?? []),
            'validade' => $dados['validade_meses'] ?? null,
            'ativo' => $dados['ativo'] ?? 1,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function atualizar(int $id, array $dados): void
    {
        $stmt = self::db()->prepare(
            'UPDATE tipos_documentos SET nome = :nome, descricao = :desc, formatos_aceites = :formatos,
             obrigatorio_para = :obrig, validade_meses = :validade, ativo = :ativo WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'desc' => $dados['descricao'] ?? null,
            'formatos' => json_encode($dados['formatos_aceites'] ?? ['pdf', 'jpg', 'png']),
            'obrig' => json_encode($dados['obrigatorio_para'] ?? []),
            'validade' => $dados['validade_meses'] ?? null,
            'ativo' => $dados['ativo'] ?? 1,
        ]);
    }
}
