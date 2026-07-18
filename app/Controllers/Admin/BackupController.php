<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\BackupService;
use App\Services\WorkflowException;

/**
 * RF47 — backup e restauração de BD e ficheiros.
 */
final class BackupController
{
    public function index(Request $request): void
    {
        echo View::render('admin/backup', [
            'tituloPagina' => 'Backup & Restauro',
            'backups' => BackupService::listar(),
        ]);
    }

    public function gerar(Request $request): void
    {
        try {
            $nome = BackupService::gerar();
            Session::flash('sucesso', "Backup gerado: {$nome}");
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/admin/backup');
    }

    public function download(Request $request, array $params): void
    {
        $caminho = BackupService::caminho((string) $params['ficheiro']);
        if (!$caminho) {
            Response::abort(404, 'Backup não encontrado.');
        }
        Response::download($caminho, basename($caminho));
    }
}
