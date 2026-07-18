<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\Documento;
use App\Models\ProcessoDocumental;
use App\Services\AuditService;
use App\Services\UploadService;
use App\Services\WorkflowException;

final class DocumentoController
{
    public function upload(Request $request, array $params): void
    {
        $processoId = (int) $params['id'];
        $processo = ProcessoDocumental::porId($processoId);
        $usuario = AuthMiddleware::usuario();

        if (!$processo || (int) $processo['despachante_id'] !== (int) $usuario['id']) {
            Response::abort(403, 'Apenas o despachante criador pode anexar documentos.');
        }
        if (!in_array($processo['status'], ['rascunho', 'aguardando_documentos', 'rejeitado'], true)) {
            Session::flash('erro', 'Não é possível anexar documentos no estado atual do processo.');
            Response::redirect('/processos/' . $processoId);
        }

        $tipoDocumentoId = (int) $request->input('tipo_documento_id', 0);
        $arquivo = $request->file('arquivo');

        if (!$tipoDocumentoId || !$arquivo) {
            Session::flash('erro', 'Selecione o tipo de documento e o ficheiro.');
            Response::redirect('/processos/' . $processoId);
        }

        try {
            UploadService::processar($arquivo, $processoId, $tipoDocumentoId, (int) $usuario['id']);
            \App\Models\HistoricoTramitacao::registar($processoId, (int) $usuario['id'], 'Documento anexado', $processo['status'], $processo['status']);
            Session::flash('sucesso', 'Documento anexado com sucesso.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processoId);
    }

    public function remover(Request $request, array $params): void
    {
        $processoId = (int) $params['id'];
        $docId = (int) $params['docId'];
        $processo = ProcessoDocumental::porId($processoId);
        $usuario = AuthMiddleware::usuario();
        $documento = Documento::porId($docId);

        if (!$processo || !$documento || (int) $documento['processo_id'] !== $processoId) {
            Response::abort(404, 'Documento não encontrado.');
        }
        if ((int) $processo['despachante_id'] !== (int) $usuario['id']) {
            Response::abort(403, 'Apenas o despachante criador pode remover documentos.');
        }
        if (!in_array($processo['status'], ['rascunho', 'aguardando_documentos', 'rejeitado'], true)) {
            Session::flash('erro', 'Não é possível remover documentos no estado atual do processo.');
            Response::redirect('/processos/' . $processoId);
        }

        $caminho = UploadService::caminhoCompleto($documento);
        Documento::remover($docId);
        if (is_file($caminho)) {
            @unlink($caminho);
        }

        \App\Models\HistoricoTramitacao::registar($processoId, (int) $usuario['id'], 'Documento removido: ' . $documento['nome_arquivo'], $processo['status'], $processo['status']);
        AuditService::log('DOC_REMOVER', 'documento', $docId, $documento['nome_arquivo']);

        Session::flash('sucesso', 'Documento removido.');
        Response::redirect('/processos/' . $processoId);
    }

    public function download(Request $request, array $params): void
    {
        $documento = Documento::porId((int) $params['id']);
        if (!$documento) {
            Response::abort(404, 'Documento não encontrado.');
        }

        $processo = ProcessoDocumental::porId((int) $documento['processo_id']);
        $usuario = AuthMiddleware::usuario();

        $ok = match ($usuario['perfil']) {
            'despachante' => (int) $processo['despachante_id'] === (int) $usuario['id'],
            'verificador' => (int) ($processo['verificador_id'] ?? 0) === (int) $usuario['id'],
            default => true,
        };
        if (!$ok) {
            Response::abort(403, 'Sem permissão para aceder a este documento.');
        }

        $caminho = UploadService::caminhoCompleto($documento);
        if (!is_file($caminho)) {
            Response::abort(404, 'Ficheiro não encontrado no servidor.');
        }

        AuditService::log('DOC_DOWNLOAD', 'documento', (int) $documento['id']);
        Response::download($caminho, $documento['nome_arquivo']);
    }

    public function marcarVerificado(Request $request, array $params): void
    {
        $documento = Documento::porId((int) $params['id']);
        $usuario = AuthMiddleware::usuario();
        if (!$documento) {
            Response::abort(404, 'Documento não encontrado.');
        }
        $processo = ProcessoDocumental::porId((int) $documento['processo_id']);
        if ((int) ($processo['verificador_id'] ?? 0) !== (int) $usuario['id']) {
            Response::abort(403, 'Apenas o verificador atribuído pode marcar este documento.');
        }

        Documento::marcarVerificado((int) $documento['id'], !$documento['verificado']);
        Response::redirect('/processos/' . $processo['id']);
    }
}
