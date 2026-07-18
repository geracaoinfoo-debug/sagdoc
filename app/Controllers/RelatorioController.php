<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditService;
use App\Services\ReportService;

/**
 * RF40/RF41 — relatórios customizados, KPIs e exportação.
 */
final class RelatorioController
{
    public function index(Request $request): void
    {
        echo View::render('relatorios/index', [
            'tituloPagina' => 'Relatórios & KPIs',
            'kpis' => ReportService::kpis(),
            'porDespachante' => ReportService::porDespachante(),
            'porVerificador' => ReportService::porVerificador(),
        ]);
    }

    public function exportar(Request $request): void
    {
        $tipo = (string) $request->query('tipo', 'processos');
        $csv = ReportService::exportarCsv($tipo);

        AuditService::log('RELATORIO_EXPORT', 'relatorio', null, $tipo);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="relatorio_' . $tipo . '_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
}
