<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\ProcessoDocumental;
use App\Services\WorkflowException;
use App\Services\WorkflowService;

/**
 * RF25 — aprovação final do Chefe de Setor + RN07 reabertura pelo Administrador.
 */
final class AprovacaoFinalController
{
    public function index(Request $request): void
    {
        echo View::render('aprovacao/index', [
            'tituloPagina' => 'Aprovação Final',
            'lista' => ProcessoDocumental::aguardandoAprovacaoFinal(),
        ]);
    }

    public function aprovar(Request $request, array $params): void
    {
        $processo = $this->carregar($params);
        $usuario = AuthMiddleware::usuario();

        try {
            WorkflowService::aprovarFinal($processo, $usuario);
            Session::flash('sucesso', 'Aprovação final concedida. Processo concluído.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function devolver(Request $request, array $params): void
    {
        $processo = $this->carregar($params);
        $usuario = AuthMiddleware::usuario();
        $motivo = trim((string) $request->input('motivo', ''));

        try {
            WorkflowService::devolverAoVerificador($processo, $usuario, $motivo ?: null);
            Session::flash('aviso', 'Processo devolvido ao verificador.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function reabrir(Request $request, array $params): void
    {
        $processo = $this->carregar($params);
        $usuario = AuthMiddleware::usuario();
        $justificativa = trim((string) $request->input('justificativa', ''));

        try {
            WorkflowService::reabrir($processo, $usuario, $justificativa);
            Session::flash('aviso', 'Processo reaberto para nova verificação.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    private function carregar(array $params): array
    {
        $processo = ProcessoDocumental::porId((int) $params['id']);
        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }
        return $processo;
    }
}
