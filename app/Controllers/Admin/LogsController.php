<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\LogAuditoria;

/**
 * RF46 — logs de auditoria (apenas administradores).
 */
final class LogsController
{
    public function index(Request $request): void
    {
        $filtros = array_filter([
            'acao' => trim((string) $request->query('acao', '')),
        ]);

        echo View::render('admin/logs', [
            'tituloPagina' => 'Logs de Auditoria',
            'lista' => LogAuditoria::recentes(300, $filtros),
            'filtros' => $filtros,
        ]);
    }
}
