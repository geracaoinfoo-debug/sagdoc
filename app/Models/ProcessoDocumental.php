<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class ProcessoDocumental extends BaseModel
{
    private const SELECT_BASE = "
        SELECT p.*, imp.nome AS importador_nome, imp.nif AS importador_nif,
               desp.nome AS despachante_nome, verif.nome AS verificador_nome
          FROM processos_documentais p
          INNER JOIN importadores imp ON imp.id = p.importador_id
          INNER JOIN usuarios desp ON desp.id = p.despachante_id
          LEFT JOIN usuarios verif ON verif.id = p.verificador_id
    ";

    public static function porId(int $id): ?array
    {
        $stmt = self::db()->prepare(self::SELECT_BASE . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function porNumeroDu(string $du): ?array
    {
        $stmt = self::db()->prepare(self::SELECT_BASE . ' WHERE p.numero_du = :du');
        $stmt->execute(['du' => $du]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): int
    {
        $pdo = self::db();
        $prioridade = Importador::ehOperadorConfiavel((int) $dados['importador_id']) ? 'operador_confiavel' : 'normal';

        $stmt = $pdo->prepare(
            'INSERT INTO processos_documentais
                (numero_du, despachante_id, importador_id, categoria, regime, status, prioridade, observacoes)
             VALUES (:du, :desp, :imp, :cat, :reg, :status, :prio, :obs)'
        );
        $stmt->execute([
            'du' => $dados['numero_du'],
            'desp' => $dados['despachante_id'],
            'imp' => $dados['importador_id'],
            'cat' => $dados['categoria'],
            'reg' => $dados['regime'],
            'status' => 'rascunho',
            'prio' => $prioridade,
            'obs' => $dados['observacoes'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function atualizarDados(int $id, array $dados): void
    {
        self::db()->prepare(
            'UPDATE processos_documentais SET categoria = :cat, regime = :reg, observacoes = :obs WHERE id = :id'
        )->execute([
            'id' => $id,
            'cat' => $dados['categoria'],
            'reg' => $dados['regime'],
            'obs' => $dados['observacoes'] ?? null,
        ]);
    }

    public static function excluirRascunho(int $id): void
    {
        self::db()->prepare("DELETE FROM processos_documentais WHERE id = :id AND status = 'rascunho'")
            ->execute(['id' => $id]);
    }

    /**
     * RN14 — cada perfil só vê os processos a que tem direito. Aplicado aqui,
     * na camada de leitura, para nunca poder ser esquecido num controller novo.
     */
    public static function paraUsuario(array $usuario, array $filtros = []): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $params = [];

        switch ($usuario['perfil']) {
            case 'despachante':
                $sql .= ' AND p.despachante_id = :uid';
                $params['uid'] = $usuario['id'];
                break;
            case 'verificador':
                $sql .= ' AND p.verificador_id = :uid';
                $params['uid'] = $usuario['id'];
                break;
            case 'chefe_setor':
                // O modelo de dados não associa o Chefe a um "setor" específico (apenas
                // verificadores têm setor); com um único setor em operação (Importação),
                // o Chefe vê todos os processos do setor, equivalente a "todos" (RN14).
                break;
            case 'gestor':
            case 'administrador':
            case 'consultor':
                // vê tudo
                break;
        }

        if (!empty($filtros['numero_du'])) {
            $sql .= ' AND p.numero_du LIKE :du';
            $params['du'] = '%' . $filtros['numero_du'] . '%';
        }
        if (!empty($filtros['importador'])) {
            $sql .= ' AND imp.nome LIKE :imp';
            $params['imp'] = '%' . $filtros['importador'] . '%';
        }
        if (!empty($filtros['despachante'])) {
            $sql .= ' AND desp.nome LIKE :desp';
            $params['desp'] = '%' . $filtros['despachante'] . '%';
        }
        if (!empty($filtros['status'])) {
            $sql .= ' AND p.status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['categoria'])) {
            $sql .= ' AND p.categoria = :categoria';
            $params['categoria'] = $filtros['categoria'];
        }
        if (!empty($filtros['data_de'])) {
            $sql .= ' AND DATE(p.data_submissao) >= :data_de';
            $params['data_de'] = $filtros['data_de'];
        }
        if (!empty($filtros['data_ate'])) {
            $sql .= ' AND DATE(p.data_submissao) <= :data_ate';
            $params['data_ate'] = $filtros['data_ate'];
        }

        $sql .= ' ORDER BY p.data_criacao DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RF17 — fila FIFO por submissão, priorizando operador_confiavel (RN10).
     */
    public static function filaVerificador(int $verificadorId): array
    {
        $stmt = self::db()->prepare(
            self::SELECT_BASE . "
             WHERE p.verificador_id = :vid AND p.status IN ('em_verificacao', 'aguardando_documentos')
             ORDER BY (p.prioridade = 'operador_confiavel') DESC, p.data_submissao ASC"
        );
        $stmt->execute(['vid' => $verificadorId]);
        return $stmt->fetchAll();
    }

    public static function aguardandoDistribuicao(): array
    {
        $stmt = self::db()->query(
            self::SELECT_BASE . " WHERE p.status = 'aguardando_distribuicao' ORDER BY p.data_submissao ASC"
        );
        return $stmt->fetchAll();
    }

    public static function aguardandoAprovacaoFinal(): array
    {
        $stmt = self::db()->query(
            self::SELECT_BASE . " WHERE p.status = 'aprovado_verificador' ORDER BY p.data_aprovacao_verificador ASC"
        );
        return $stmt->fetchAll();
    }

    public static function contarPorStatus(array $usuario): array
    {
        $lista = self::paraUsuario($usuario);
        $out = [];
        foreach ($lista as $p) {
            $out[$p['status']] = ($out[$p['status']] ?? 0) + 1;
        }
        return $out;
    }

    /**
     * Carga atual (em_verificacao) por cada verificador — usado na distribuição manual e no painel do Chefe.
     */
    public static function cargaPorVerificador(): array
    {
        $sql = "
            SELECT u.id, u.nome, v.matricula, v.setor,
                   COUNT(p.id) AS carga
              FROM usuarios u
              INNER JOIN verificadores v ON v.usuario_id = u.id
              LEFT JOIN processos_documentais p
                     ON p.verificador_id = u.id AND p.status = 'em_verificacao'
             WHERE u.ativo = 1
             GROUP BY u.id, u.nome, v.matricula, v.setor
             ORDER BY carga ASC, u.nome ASC
        ";
        return self::db()->query($sql)->fetchAll();
    }

    public static function todos(): array
    {
        $stmt = self::db()->query(self::SELECT_BASE . ' ORDER BY p.data_criacao DESC');
        return $stmt->fetchAll();
    }

    public static function estatisticasGerais(): array
    {
        return self::db()->query('SELECT * FROM v_processos_completos')->fetchAll();
    }

    public static function estatisticasVerificadores(): array
    {
        return self::db()->query('SELECT * FROM v_estatisticas_verificador')->fetchAll();
    }
}
