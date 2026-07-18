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
 * RF17-RF24 — fila do verificador e ações de verificação técnica.
 */
final class VerificacaoController
{
    public function fila(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        echo View::render('verificacao/fila', [
            'tituloPagina' => 'Fila de Trabalho',
            'lista' => ProcessoDocumental::filaVerificador((int) $usuario['id']),
        ]);
    }

    public function aprovar(Request $request, array $params): void
    {
        $processo = $this->carregar($params);
        $usuario = AuthMiddleware::usuario();
        $parecer = trim((string) $request->input('parecer', ''));

        if ($parecer === '') {
            Session::flash('erro', 'É obrigatório indicar o parecer técnico.');
            Response::redirect('/processos/' . $processo['id']);
        }

        try {
            WorkflowService::aprovarVerificador($processo, $usuario, $parecer);
            Session::flash('sucesso', 'Processo aprovado. Chefe de Setor notificado.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function solicitarDocs(Request $request, array $params): void
    {
        $processo = $this->carregar($params);
        $usuario = AuthMiddleware::usuario();
        $motivo = trim((string) $request->input('motivo', ''));

        if ($motivo === '') {
            Session::flash('erro', 'Indique o que é necessário solicitar.');
            Response::redirect('/processos/' . $processo['id']);
        }

        try {
            WorkflowService::solicitarDocumentos($processo, $usuario, $motivo);
            Session::flash('aviso', 'Solicitação enviada ao despachante.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function rejeitar(Request $request, array $params): void
    {
        $processo = $this->carregar($params);
        $usuario = AuthMiddleware::usuario();
        $motivo = trim((string) $request->input('motivo', ''));

        if ($motivo === '') {
            Session::flash('erro', 'Especifique o motivo da rejeição.');
            Response::redirect('/processos/' . $processo['id']);
        }

        try {
            WorkflowService::rejeitar($processo, $usuario, $motivo);
            Session::flash('erro', 'Processo rejeitado e devolvido ao despachante.');
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
