<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuracao;

/**
 * RF26/RN11/§10 — semáforo de SLA por fase do processo.
 */
final class SLAService
{
    public static function status(array $processo): array
    {
        [$limite, $desde, $fase] = match ($processo['status']) {
            'aguardando_distribuicao' => [
                (int) Configuracao::get('sla_distribuicao_horas', 4),
                $processo['data_submissao'],
                'Distribuição',
            ],
            'em_verificacao', 'aguardando_documentos' => [
                (int) Configuracao::get('sla_verificacao_horas', 48),
                $processo['data_distribuicao'] ?? $processo['data_submissao'],
                'Verificação',
            ],
            'aprovado_verificador' => [
                (int) Configuracao::get('sla_aprovacao_chefe_horas', 24),
                $processo['data_aprovacao_verificador'],
                'Aprovação do Chefe',
            ],
            default => [0, null, '—'],
        };

        if ($limite === 0 || $desde === null) {
            return ['cor' => 'verde', 'fase' => $fase, 'horas' => 0.0, 'limite' => 0, 'label' => '—', 'desde' => null];
        }

        $horas = (strtotime('now') - strtotime($desde)) / 3600;
        $cor = 'verde';
        if ($horas > $limite) {
            $cor = 'vermelho';
        } elseif ($horas > $limite * 0.75) {
            $cor = 'amarelo';
        }

        return [
            'cor' => $cor,
            'fase' => $fase,
            'horas' => round($horas, 1),
            'limite' => $limite,
            'label' => round($horas) . 'h / ' . $limite . 'h',
            'desde' => $desde,
        ];
    }

    public static function limiteTotalHoras(): int
    {
        return (int) Configuracao::get('sla_distribuicao_horas', 4)
            + (int) Configuracao::get('sla_verificacao_horas', 48)
            + (int) Configuracao::get('sla_aprovacao_chefe_horas', 24);
    }

    /**
     * RN15/§10 — usado pelo job de reavaliação periódica (bin/sla_check.php) para
     * identificar processos que ultrapassaram o SLA e ainda não geraram alerta.
     */
    public static function ultrapassados(array $processos): array
    {
        return array_values(array_filter($processos, fn ($p) => self::status($p)['cor'] === 'vermelho'));
    }
}
