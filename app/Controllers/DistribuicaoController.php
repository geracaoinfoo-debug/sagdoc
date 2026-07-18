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
 * RF15/RF16 — distribuição automática (balanceamento de carga) e manual.
 */
final class DistribuicaoController
{
    public function index(Request $request): void
    {
        echo View::render('distribuicao/index', [
            'tituloPagina' => 'Distribuição de Processos',
            'lista' => ProcessoDocumental::aguardandoDistribuicao(),
            'verificadores' => ProcessoDocumental::cargaPorVerificador(),
        ]);
    }

    public function automatica(Request $request): void
    {
        $lista = ProcessoDocumental::aguardandoDistribuicao();
        $distribuidos = 0;
        foreach ($lista as $processo) {
            try {
                WorkflowService::distribuirAutomatico($processo);
                $distribuidos++;
            } catch (WorkflowException) {
                // processo já não está mais aguardando distribuição; ignora.
            }
        }

        Session::flash('sucesso', "Distribuição automática concluída: {$distribuidos} processo(s) atribuído(s) (RF15).");
        Response::redirect('/distribuicao');
    }

    public function manual(Request $request, array $params): void
    {
        $processo = ProcessoDocumental::porId((int) $params['id']);
        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }

        $usuario = AuthMiddleware::usuario();
        $verificadorId = (int) $request->input('verificador_id', 0);

        try {
            WorkflowService::distribuirManual($processo, $verificadorId, $usuario);
            Session::flash('sucesso', 'Processo ' . $processo['numero_du'] . ' atribuído.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/distribuicao');
    }

    public function reatribuirRejeitado(Request $request, array $params): void
    {
        $processo = ProcessoDocumental::porId((int) $params['id']);
        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }

        $usuario = AuthMiddleware::usuario();
        $verificadorId = (int) $request->input('verificador_id', 0);

        try {
            WorkflowService::reatribuirPeloChefe($processo, $usuario, $verificadorId);
            Session::flash('sucesso', 'Processo reatribuído e reaberto para verificação.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }
}
