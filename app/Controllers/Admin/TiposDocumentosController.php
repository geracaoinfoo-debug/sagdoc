<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\TipoDocumento;
use App\Services\AuditService;

/**
 * RF42/RF43 — tipos de documento e associação a categorias de mercadoria.
 */
final class TiposDocumentosController
{
    public function index(Request $request): void
    {
        echo View::render('admin/tipos_documentos', [
            'tituloPagina' => 'Tipos de Documento',
            'lista' => TipoDocumento::todos(),
        ]);
    }

    public function criar(Request $request): void
    {
        $nome = trim((string) $request->input('nome', ''));
        if ($nome === '') {
            Session::flash('erro', 'Indique o nome do documento.');
            Response::redirect('/admin/tipos-documentos');
        }

        $obrigatorioPara = array_filter(array_map('trim', explode(',', (string) $request->input('obrigatorio_para', ''))));

        $id = TipoDocumento::criar([
            'nome' => $nome,
            'descricao' => trim((string) $request->input('descricao', '')) ?: null,
            'obrigatorio_para' => array_values($obrigatorioPara),
            'validade_meses' => $request->input('validade_meses') ? (int) $request->input('validade_meses') : null,
            'ativo' => 1,
        ]);

        AuditService::log('TIPODOC_CRIAR', 'tipo_documento', $id, $nome);
        Session::flash('sucesso', 'Tipo de documento criado.');
        Response::redirect('/admin/tipos-documentos');
    }

    public function atualizar(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $tipo = TipoDocumento::porId($id);
        if (!$tipo) {
            Response::abort(404, 'Tipo de documento não encontrado.');
        }

        $obrigatorioPara = array_filter(array_map('trim', explode(',', (string) $request->input('obrigatorio_para', ''))));

        TipoDocumento::atualizar($id, [
            'nome' => trim((string) $request->input('nome', $tipo['nome'])),
            'descricao' => trim((string) $request->input('descricao', '')) ?: null,
            'obrigatorio_para' => array_values($obrigatorioPara),
            'validade_meses' => $request->input('validade_meses') ? (int) $request->input('validade_meses') : null,
            'ativo' => $request->input('ativo') ? 1 : 0,
        ]);

        AuditService::log('TIPODOC_ATUALIZAR', 'tipo_documento', $id);
        Session::flash('sucesso', 'Tipo de documento atualizado.');
        Response::redirect('/admin/tipos-documentos');
    }
}
