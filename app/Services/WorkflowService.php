<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\HistoricoTramitacao;
use App\Models\ProcessoDocumental;
use App\Models\Usuario;

/**
 * §9 — máquina de estados do processo. Este serviço é o único caminho que
 * altera processos_documentais.status: valida ator/permissão e pré-condições
 * (RN), grava historico_tramitacao + logs_auditoria, e dispara notificações
 * (§11). O trigger tr_processo_status_change é apenas um backstop passivo
 * para alterações feitas fora da aplicação.
 */
final class WorkflowService
{
    private const LIMITE_REENVIOS = 3;

    /**
     * RF13/RN03 — rascunho → aguardando_distribuicao.
     */
    public static function submeter(array $processo, array $ator): array
    {
        self::garantirDono($processo, $ator);
        self::garantirEstado($processo, ['rascunho']);

        if (!ChecklistService::obrigatoriosCompletos($processo)) {
            throw new WorkflowException('Faltam documentos obrigatórios. Não é possível submeter o processo.');
        }

        self::mudarEstado($processo, 'aguardando_distribuicao', $ator, 'Processo submetido para verificação', [
            'data_submissao' => date('Y-m-d H:i:s'),
        ]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);

        foreach (Usuario::porPerfil('chefe_setor') as $chefe) {
            NotificationService::enviar(
                (int) $chefe['id'],
                'distribuicao',
                'Processo aguarda distribuição',
                "O processo {$atualizado['numero_du']} está pronto para ser distribuído.",
                '/processos/' . $atualizado['id']
            );
        }

        return $atualizado;
    }

    public static function cancelar(array $processo, array $ator): array
    {
        self::garantirDono($processo, $ator);
        self::garantirEstado($processo, ['rascunho']);

        self::mudarEstado($processo, 'cancelado', $ator, 'Processo cancelado pelo despachante');

        return ProcessoDocumental::porId((int) $processo['id']);
    }

    /**
     * RF15 — distribuição automática por balanceamento de carga (procedure SQL).
     */
    public static function distribuirAutomatico(array $processo): array
    {
        self::garantirEstado($processo, ['aguardando_distribuicao']);

        $pdo = Database::connection();
        $pdo->prepare('SET @sagdoc_skip_trigger = NULL')->execute();
        $stmt = $pdo->prepare('CALL atribuir_verificador_automatico(:id)');
        $stmt->execute(['id' => $processo['id']]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);

        if ($atualizado['status'] === 'em_verificacao') {
            AuditService::log('PROC_DISTRIBUIR_AUTO', 'processo', (int) $processo['id']);
            NotificationService::processoDistribuido($atualizado);
        }

        return $atualizado;
    }

    /**
     * RF16 — distribuição/redistribuição manual pelo Chefe de Setor.
     */
    public static function distribuirManual(array $processo, int $verificadorId, array $ator): array
    {
        self::garantirPerfil($ator, ['chefe_setor', 'administrador']);
        self::garantirEstado($processo, ['aguardando_distribuicao']);

        $verificador = Usuario::porId($verificadorId);
        if (!$verificador || $verificador['perfil'] !== 'verificador') {
            throw new WorkflowException('Verificador inválido.');
        }

        self::mudarEstado($processo, 'em_verificacao', $ator, "Distribuído a {$verificador['nome']} (manual — Chefe de Setor)", [
            'verificador_id' => $verificadorId,
            'data_distribuicao' => date('Y-m-d H:i:s'),
        ]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::processoDistribuido($atualizado);

        return $atualizado;
    }

    /**
     * RF23/UC12 — aprovação técnica do verificador.
     */
    public static function aprovarVerificador(array $processo, array $ator, string $parecer): array
    {
        self::garantirVerificadorAtribuido($processo, $ator);
        self::garantirEstado($processo, ['em_verificacao']);

        self::mudarEstado($processo, 'aprovado_verificador', $ator, 'Aprovado pelo verificador', [
            'parecer_tecnico' => $parecer,
            'data_aprovacao_verificador' => date('Y-m-d H:i:s'),
        ], $parecer);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::aprovadoPeloVerificador($atualizado, Usuario::porPerfil('chefe_setor'));

        return $atualizado;
    }

    /**
     * RF20/RF21 — solicitação de documentos adicionais.
     */
    public static function solicitarDocumentos(array $processo, array $ator, string $motivo): array
    {
        self::garantirVerificadorAtribuido($processo, $ator);
        self::garantirEstado($processo, ['em_verificacao']);

        self::mudarEstado($processo, 'aguardando_documentos', $ator, 'Solicitados documentos adicionais', [
            'motivo_rejeicao' => $motivo,
        ], $motivo);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::documentosSolicitados($atualizado, $motivo);

        return $atualizado;
    }

    /**
     * RF24 — rejeição pelo verificador.
     */
    public static function rejeitar(array $processo, array $ator, string $motivo): array
    {
        self::garantirVerificadorAtribuido($processo, $ator);
        self::garantirEstado($processo, ['em_verificacao']);

        self::mudarEstado($processo, 'rejeitado', $ator, 'Processo rejeitado', [
            'motivo_rejeicao' => $motivo,
        ], $motivo);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::rejeitado($atualizado, $motivo);

        return $atualizado;
    }

    /**
     * RF22 — despachante reanexa documentos e a solicitação regressa à fila do verificador.
     */
    public static function responderSolicitacao(array $processo, array $ator): array
    {
        self::garantirDono($processo, $ator);
        self::garantirEstado($processo, ['aguardando_documentos']);

        self::mudarEstado($processo, 'em_verificacao', $ator, 'Despachante respondeu à solicitação de documentos', [
            'motivo_rejeicao' => null,
        ]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::despachanteRespondeu($atualizado);

        return $atualizado;
    }

    /**
     * RN05 — corrigir e reenviar após rejeição, até 3 vezes; a partir daí exige
     * intervenção do Chefe de Setor (ver reatribuirPeloChefe).
     */
    public static function reenviarRejeitado(array $processo, array $ator): array
    {
        self::garantirDono($processo, $ator);
        self::garantirEstado($processo, ['rejeitado']);

        $tentativas = (int) $processo['tentativas_submissao'];
        if ($tentativas >= self::LIMITE_REENVIOS) {
            throw new WorkflowException(
                'Este processo já foi rejeitado ' . self::LIMITE_REENVIOS . ' vezes. ' .
                'É necessária a intervenção do Chefe de Setor para prosseguir.'
            );
        }

        if (!ChecklistService::obrigatoriosCompletos($processo)) {
            throw new WorkflowException('Faltam documentos obrigatórios. Não é possível reenviar o processo.');
        }

        self::mudarEstado($processo, 'em_verificacao', $ator, 'Processo corrigido e reenviado pelo despachante', [
            'tentativas_submissao' => $tentativas + 1,
            'motivo_rejeicao' => null,
        ]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::despachanteRespondeu($atualizado);

        return $atualizado;
    }

    /**
     * RN05 (3ª rejeição) — intervenção do Chefe de Setor para reatribuir manualmente.
     */
    public static function reatribuirPeloChefe(array $processo, array $ator, int $verificadorId): array
    {
        self::garantirPerfil($ator, ['chefe_setor', 'administrador']);
        self::garantirEstado($processo, ['rejeitado']);

        $verificador = Usuario::porId($verificadorId);
        if (!$verificador || $verificador['perfil'] !== 'verificador') {
            throw new WorkflowException('Verificador inválido.');
        }

        self::mudarEstado($processo, 'em_verificacao', $ator, 'Reatribuído pelo Chefe de Setor após 3 rejeições', [
            'verificador_id' => $verificadorId,
            'motivo_rejeicao' => null,
        ]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::processoDistribuido($atualizado);

        return $atualizado;
    }

    /**
     * RF25 — aprovação final do Chefe de Setor.
     */
    public static function aprovarFinal(array $processo, array $ator): array
    {
        self::garantirPerfil($ator, ['chefe_setor', 'administrador']);
        self::garantirEstado($processo, ['aprovado_verificador']);

        self::mudarEstado($processo, 'aprovado_final', $ator, 'Aprovação final concedida', [
            'data_aprovacao_final' => date('Y-m-d H:i:s'),
        ]);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::aprovacaoFinal($atualizado);

        return $atualizado;
    }

    /**
     * Devolução ao verificador pelo Chefe (não conforme para aprovação final).
     */
    public static function devolverAoVerificador(array $processo, array $ator, ?string $motivo): array
    {
        self::garantirPerfil($ator, ['chefe_setor', 'administrador']);
        self::garantirEstado($processo, ['aprovado_verificador']);

        self::mudarEstado($processo, 'em_verificacao', $ator, 'Devolvido ao verificador pelo Chefe', [
            'data_aprovacao_verificador' => null,
        ], $motivo);

        $atualizado = ProcessoDocumental::porId((int) $processo['id']);
        NotificationService::devolvido($atualizado, $motivo ?? 'Necessária revisão adicional.');

        return $atualizado;
    }

    /**
     * RN07 — aprovação final é irreversível; só o Administrador pode reabrir, com justificativa.
     */
    public static function reabrir(array $processo, array $ator, string $justificativa): array
    {
        self::garantirPerfil($ator, ['administrador']);
        self::garantirEstado($processo, ['aprovado_final']);

        if (trim($justificativa) === '') {
            throw new WorkflowException('É obrigatório indicar uma justificativa para reabrir o processo.');
        }

        self::mudarEstado($processo, 'em_verificacao', $ator, 'Processo reaberto pelo Administrador', [
            'data_aprovacao_final' => null,
        ], $justificativa);

        return ProcessoDocumental::porId((int) $processo['id']);
    }

    // ---------------------------------------------------------------
    // Núcleo comum
    // ---------------------------------------------------------------

    private static function mudarEstado(array $processo, string $novoStatus, array $ator, string $acao, array $camposExtra = [], ?string $observacao = null): void
    {
        $pdo = Database::connection();

        $sets = ['status = :status'];
        $params = ['status' => $novoStatus, 'id' => $processo['id']];
        foreach ($camposExtra as $campo => $valor) {
            $sets[] = "$campo = :$campo";
            $params[$campo] = $valor;
        }

        $pdo->prepare('SET @sagdoc_skip_trigger = 1')->execute();
        $pdo->prepare('UPDATE processos_documentais SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
        $pdo->prepare('SET @sagdoc_skip_trigger = NULL')->execute();

        HistoricoTramitacao::registar(
            (int) $processo['id'],
            $ator['id'] ?? null,
            $acao,
            $processo['status'],
            $novoStatus,
            $observacao
        );

        AuditService::log('PROC_' . strtoupper($novoStatus), 'processo', (int) $processo['id'], $acao);
    }

    private static function garantirEstado(array $processo, array $estadosPermitidos): void
    {
        if (!in_array($processo['status'], $estadosPermitidos, true)) {
            throw new WorkflowException(
                "Transição inválida: o processo está em '{$processo['status']}', " .
                'não é possível executar esta ação a partir deste estado.'
            );
        }
    }

    private static function garantirDono(array $processo, array $ator): void
    {
        if ((int) $processo['despachante_id'] !== (int) $ator['id']) {
            throw new WorkflowException('Apenas o despachante criador pode executar esta ação.');
        }
    }

    private static function garantirVerificadorAtribuido(array $processo, array $ator): void
    {
        if ((int) ($processo['verificador_id'] ?? 0) !== (int) $ator['id']) {
            throw new WorkflowException('Apenas o verificador atribuído pode executar esta ação.');
        }
    }

    private static function garantirPerfil(array $ator, array $perfis): void
    {
        if (!in_array($ator['perfil'], $perfis, true)) {
            throw new WorkflowException('O seu perfil não tem permissão para executar esta ação.');
        }
    }
}
