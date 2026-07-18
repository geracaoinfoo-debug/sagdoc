<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProcessoDocumental;
use App\Models\Usuario;

/**
 * RF40/RF41 — relatórios customizados, KPIs e exportação (CSV — abre em Excel).
 */
final class ReportService
{
    public static function kpis(): array
    {
        $processos = ProcessoDocumental::todos();
        $total = count($processos);
        $concluidos = array_values(array_filter($processos, fn ($p) => $p['data_aprovacao_final'] !== null));
        $rejeitados = count(array_filter($processos, fn ($p) => $p['status'] === 'rejeitado'));

        $tempos = array_map(
            fn ($p) => (strtotime($p['data_aprovacao_final']) - strtotime($p['data_submissao'])) / 3600,
            $concluidos
        );
        $tempoMedio = $tempos ? array_sum($tempos) / count($tempos) : 0.0;

        $limiteTotal = SLAService::limiteTotalHoras();
        $dentroSla = count(array_filter($tempos, fn ($h) => $h <= $limiteTotal));
        $percentualSla = $concluidos ? round(($dentroSla / count($concluidos)) * 100) : 0;

        $reenviados = count(array_filter($processos, fn ($p) => (int) $p['tentativas_submissao'] > 0));

        return [
            'total_processos' => $total,
            'tempo_medio_horas' => round($tempoMedio, 1),
            'percentual_dentro_sla' => $percentualSla,
            'total_rejeicoes' => $rejeitados,
            'total_retrabalho' => $reenviados,
            'taxa_aprovacao' => $total ? round((count(array_filter($processos, fn ($p) => $p['status'] === 'aprovado_final')) / $total) * 100) : 0,
        ];
    }

    public static function porDespachante(): array
    {
        $processos = ProcessoDocumental::todos();
        $despachantes = Usuario::porPerfil('despachante');
        $out = [];
        foreach ($despachantes as $d) {
            $meus = array_filter($processos, fn ($p) => (int) $p['despachante_id'] === (int) $d['id']);
            $out[] = [
                'nome' => $d['nome'],
                'total' => count($meus),
                'aprovados' => count(array_filter($meus, fn ($p) => $p['status'] === 'aprovado_final')),
                'rejeitados' => count(array_filter($meus, fn ($p) => $p['status'] === 'rejeitado')),
            ];
        }
        return $out;
    }

    public static function porVerificador(): array
    {
        return ProcessoDocumental::estatisticasVerificadores();
    }

    public static function exportarCsv(string $tipo): string
    {
        $linhas = [];
        if ($tipo === 'despachantes') {
            $linhas[] = ['Despachante', 'Total', 'Aprovados', 'Rejeitados'];
            foreach (self::porDespachante() as $r) {
                $linhas[] = [$r['nome'], $r['total'], $r['aprovados'], $r['rejeitados']];
            }
        } elseif ($tipo === 'verificadores') {
            $linhas[] = ['Verificador', 'Total', 'Em Verificação', 'Aprovados', 'Tempo Médio (h)'];
            foreach (self::porVerificador() as $r) {
                $linhas[] = [$r['verificador_nome'], $r['total_processos'], $r['em_verificacao'], $r['aprovados'], round((float) $r['tempo_medio_horas'], 1)];
            }
        } else {
            $linhas[] = ['Nº DU', 'Importador', 'Categoria', 'Despachante', 'Verificador', 'Estado', 'Data Submissão'];
            foreach (ProcessoDocumental::todos() as $p) {
                $linhas[] = [
                    $p['numero_du'], $p['importador_nome'], $p['categoria'], $p['despachante_nome'],
                    $p['verificador_nome'] ?? '—', status_label($p['status']), $p['data_submissao'] ?? '—',
                ];
            }
        }

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        foreach ($linhas as $linha) {
            fputcsv($fh, $linha, ';');
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
