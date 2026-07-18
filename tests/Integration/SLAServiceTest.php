<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\SLAService;
use PHPUnit\Framework\TestCase;

/**
 * RF26/RN11/§10 — semáforo de SLA (verde <75%, amarelo 75-100%, vermelho >100%).
 */
final class SLAServiceTest extends TestCase
{
    public function testVerdeQuandoDentroDosPrimeiros75PorCentoDoPrazo(): void
    {
        $limite = (int) \App\Models\Configuracao::get('sla_verificacao_horas', 48);
        $processo = $this->processoEmVerificacaoHaHoras((int) round($limite * 0.3));

        $this->assertSame('verde', SLAService::status($processo)['cor']);
    }

    public function testAmareloEntre75E100PorCentoDoPrazo(): void
    {
        $limite = (int) \App\Models\Configuracao::get('sla_verificacao_horas', 48);
        $processo = $this->processoEmVerificacaoHaHoras((int) round($limite * 0.9));

        $this->assertSame('amarelo', SLAService::status($processo)['cor']);
    }

    public function testVermelhoAcimaDe100PorCentoDoPrazo(): void
    {
        $limite = (int) \App\Models\Configuracao::get('sla_verificacao_horas', 48);
        $processo = $this->processoEmVerificacaoHaHoras($limite + 10);

        $this->assertSame('vermelho', SLAService::status($processo)['cor']);
    }

    public function testEstadosSemFaseDeSlaDevolvemVerdeSemAlerta(): void
    {
        $processo = ['status' => 'aprovado_final', 'data_submissao' => null, 'data_distribuicao' => null, 'data_aprovacao_verificador' => null];
        $status = SLAService::status($processo);

        $this->assertSame('verde', $status['cor']);
        $this->assertSame(0, $status['limite']);
    }

    private function processoEmVerificacaoHaHoras(int $horas): array
    {
        $desde = date('Y-m-d H:i:s', strtotime("-{$horas} hours"));
        return [
            'status' => 'em_verificacao',
            'data_submissao' => $desde,
            'data_distribuicao' => $desde,
            'data_aprovacao_verificador' => null,
        ];
    }
}
