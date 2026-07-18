<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Documento;
use App\Models\HistoricoTramitacao;
use App\Models\ProcessoDocumental;
use App\Models\Usuario;
use App\Services\WorkflowException;
use App\Services\WorkflowService;
use PHPUnit\Framework\TestCase;

/**
 * §9 (máquina de estados), RN03 (checklist completo para submeter),
 * RN05 (limite de 3 reenvios), RN06 (hierarquia verificador→chefe),
 * RN07 (irreversibilidade da aprovação final).
 */
final class WorkflowServiceTest extends TestCase
{
    private array $despachante;
    private array $verificador;
    private array $chefe;
    private array $admin;
    private int $processoId;

    protected function setUp(): void
    {
        $this->despachante = Usuario::porUsername('jbarbosa');
        $this->verificador = Usuario::porUsername('averificador');
        $this->chefe = Usuario::porUsername('chefe');
        $this->admin = Usuario::porUsername('admin');

        $du = '2025/' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $this->processoId = ProcessoDocumental::criar([
            'numero_du' => $du,
            'despachante_id' => $this->despachante['id'],
            'importador_id' => 1,
            'categoria' => 'Geral', // exige apenas Fatura(1), B/L(2), Packing(3)
            'regime' => 'Trânsito',
        ]);
    }

    protected function tearDown(): void
    {
        Database::connection()->prepare('DELETE FROM processos_documentais WHERE id = :id')
            ->execute(['id' => $this->processoId]);
    }

    private function anexarDocumentosObrigatorios(): void
    {
        foreach ([1, 2, 3] as $tipoId) {
            Documento::criar([
                'processo_id' => $this->processoId,
                'tipo_documento_id' => $tipoId,
                'nome_arquivo' => "doc-teste-{$tipoId}.pdf",
                'caminho_arquivo' => "{$this->processoId}/doc-teste-{$tipoId}.pdf",
                'tamanho_bytes' => 1000,
                'hash_sha256' => hash('sha256', "teste-{$tipoId}"),
                'upload_por' => $this->despachante['id'],
            ]);
        }
    }

    private function processo(): array
    {
        return ProcessoDocumental::porId($this->processoId);
    }

    public function testNaoPermiteSubmeterSemDocumentosObrigatorios(): void
    {
        $this->expectException(WorkflowException::class);
        WorkflowService::submeter($this->processo(), $this->despachante);
    }

    public function testFluxoCompletoAteAprovacaoFinal(): void
    {
        $this->anexarDocumentosObrigatorios();

        $processo = WorkflowService::submeter($this->processo(), $this->despachante);
        $this->assertSame('aguardando_distribuicao', $processo['status']);

        $processo = WorkflowService::distribuirManual($processo, (int) $this->verificador['id'], $this->chefe);
        $this->assertSame('em_verificacao', $processo['status']);
        $this->assertSame((int) $this->verificador['id'], (int) $processo['verificador_id']);

        $processo = WorkflowService::aprovarVerificador($processo, $this->verificador, 'Conforme.');
        $this->assertSame('aprovado_verificador', $processo['status']);

        $processo = WorkflowService::aprovarFinal($processo, $this->chefe);
        $this->assertSame('aprovado_final', $processo['status']);
        $this->assertNotNull($processo['data_aprovacao_final']);

        $historico = HistoricoTramitacao::porProcesso($this->processoId);
        $this->assertGreaterThanOrEqual(4, count($historico));
    }

    public function testVerificadorNaoAtribuidoNaoPodeAprovar(): void
    {
        $this->anexarDocumentosObrigatorios();
        $processo = WorkflowService::submeter($this->processo(), $this->despachante);
        $processo = WorkflowService::distribuirManual($processo, (int) $this->verificador['id'], $this->chefe);

        $outroVerificador = Usuario::porUsername('nverificador');

        $this->expectException(WorkflowException::class);
        WorkflowService::aprovarVerificador($processo, $outroVerificador, 'tentativa indevida');
    }

    public function testRN05LimitaReenviosATres(): void
    {
        $this->anexarDocumentosObrigatorios();
        $processo = WorkflowService::submeter($this->processo(), $this->despachante);
        $processo = WorkflowService::distribuirManual($processo, (int) $this->verificador['id'], $this->chefe);

        // 3 ciclos de rejeição → reenvio são permitidos (RN05).
        for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
            $processo = WorkflowService::rejeitar($processo, $this->verificador, "motivo {$tentativa}");
            $this->assertSame('rejeitado', $processo['status']);

            $processo = WorkflowService::reenviarRejeitado($processo, $this->despachante);
            $this->assertSame('em_verificacao', $processo['status']);
        }

        $this->assertSame(3, (int) $processo['tentativas_submissao']);

        // A 4ª rejeição exige intervenção do Chefe: o despachante já não pode reenviar sozinho.
        $processo = WorkflowService::rejeitar($processo, $this->verificador, 'motivo 4');
        $this->assertSame('rejeitado', $processo['status']);

        $this->expectException(WorkflowException::class);
        WorkflowService::reenviarRejeitado($processo, $this->despachante);
    }

    public function testRN07AprovacaoFinalSoPodeSerReabertaPeloAdministrador(): void
    {
        $this->anexarDocumentosObrigatorios();
        $processo = WorkflowService::submeter($this->processo(), $this->despachante);
        $processo = WorkflowService::distribuirManual($processo, (int) $this->verificador['id'], $this->chefe);
        $processo = WorkflowService::aprovarVerificador($processo, $this->verificador, 'Conforme.');
        $processo = WorkflowService::aprovarFinal($processo, $this->chefe);

        try {
            WorkflowService::reabrir($processo, $this->chefe, 'tentativa indevida pelo chefe');
            $this->fail('Chefe de Setor não deveria poder reabrir um processo aprovado (RN07).');
        } catch (WorkflowException) {
            // esperado
        }

        $reaberto = WorkflowService::reabrir($processo, $this->admin, 'Erro detetado após auditoria.');
        $this->assertSame('em_verificacao', $reaberto['status']);
    }

    public function testTransicaoInvalidaEhRejeitada(): void
    {
        // Processo em rascunho não pode ser aprovado diretamente.
        $this->expectException(WorkflowException::class);
        WorkflowService::aprovarVerificador($this->processo(), $this->verificador, 'parecer');
    }
}
