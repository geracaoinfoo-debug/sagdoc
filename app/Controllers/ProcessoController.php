<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Comunicacao;
use App\Models\HistoricoTramitacao;
use App\Models\Importador;
use App\Models\ProcessoDocumental;
use App\Models\Usuario;
use App\Services\AuditService;
use App\Services\ChecklistService;
use App\Services\DossieService;
use App\Services\WorkflowException;
use App\Services\WorkflowService;

final class ProcessoController
{
    private const CATEGORIAS = ['Alimentos', 'Medicamentos', 'Vegetais', 'Animais', 'Químicos', 'Têxteis', 'Electrónicos', 'Veículos', 'Geral'];
    private const REGIMES = ['Importação Definitiva', 'Reimportação', 'Trânsito', 'Admissão Temporária', 'Entreposto Aduaneiro', 'Exportação'];

    public function index(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        $meus = ProcessoDocumental::paraUsuario($usuario);
        echo View::render('processo/index', ['tituloPagina' => 'Meus Processos', 'lista' => $meus]);
    }

    public function novoForm(Request $request): void
    {
        echo View::render('processo/novo', [
            'tituloPagina' => 'Novo Processo',
            'categorias' => self::CATEGORIAS,
            'regimes' => self::REGIMES,
            'importadores' => Importador::todos(),
        ]);
    }

    public function criar(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        $du = trim((string) $request->input('numero_du', ''));
        $categoria = (string) $request->input('categoria', '');
        $regime = (string) $request->input('regime', '');
        $observacoes = trim((string) $request->input('observacoes', ''));
        $importadorId = $request->input('importador_id');

        if (!valida_numero_du($du)) {
            Session::flash('erro', 'Formato de nº de DU inválido. Utilize AAAA/NNNNNN (ex: 2025/001234).');
            Response::redirect('/processos/novo');
        }

        if (ProcessoDocumental::porNumeroDu($du) !== null) {
            Session::flash('erro', 'Já existe um processo associado a esta DU.');
            Response::redirect('/processos/novo');
        }

        if ($importadorId === 'novo') {
            $nome = trim((string) $request->input('novo_importador_nome', ''));
            $nif = trim((string) $request->input('novo_importador_nif', ''));
            if ($nome === '' || !valida_nif($nif)) {
                Session::flash('erro', 'Indique nome e NIF válidos para o novo importador.');
                Response::redirect('/processos/novo');
            }
            $existente = Importador::porNif($nif);
            $importadorId = $existente ? $existente['id'] : Importador::criar($nome, $nif);
        }

        if (!$categoria || !$regime || !$importadorId) {
            Session::flash('erro', 'Preencha todos os campos obrigatórios.');
            Response::redirect('/processos/novo');
        }

        $id = ProcessoDocumental::criar([
            'numero_du' => $du,
            'despachante_id' => $usuario['id'],
            'importador_id' => (int) $importadorId,
            'categoria' => $categoria,
            'regime' => $regime,
            'observacoes' => $observacoes ?: null,
        ]);

        HistoricoTramitacao::registar($id, (int) $usuario['id'], 'Processo criado (rascunho)', null, 'rascunho');
        AuditService::log('PROC_CRIAR', 'processo', $id, "DU {$du}");

        Session::flash('sucesso', 'Processo criado em rascunho. Anexe os documentos obrigatórios.');
        Response::redirect('/processos/' . $id);
    }

    public function detalhe(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $processo = ProcessoDocumental::porId($id);
        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }

        $usuario = AuthMiddleware::usuario();
        $this->garantirVisibilidade($processo, $usuario);

        Comunicacao::marcarLidas($id, (int) $usuario['id']);

        echo View::render('processo/detalhe', [
            'tituloPagina' => 'Processo ' . $processo['numero_du'],
            'processo' => $processo,
            'checklist' => ChecklistService::estadoDoProcesso($processo),
            'historico' => HistoricoTramitacao::porProcesso($id),
            'mensagens' => Comunicacao::porProcesso($id),
            'verificadores' => Usuario::porPerfil('verificador'),
            'usuario' => $usuario,
            'mostrarRecibo' => $request->query('recibo') === '1',
        ]);
    }

    public function atualizar(Request $request, array $params): void
    {
        $processo = $this->carregarDoDono($params);

        if ($processo['status'] !== 'rascunho') {
            Session::flash('erro', 'Só é possível editar dados enquanto o processo está em rascunho.');
            Response::redirect('/processos/' . $processo['id']);
        }

        ProcessoDocumental::atualizarDados((int) $processo['id'], [
            'categoria' => (string) $request->input('categoria', $processo['categoria']),
            'regime' => (string) $request->input('regime', $processo['regime']),
            'observacoes' => trim((string) $request->input('observacoes', '')) ?: null,
        ]);

        AuditService::log('PROC_ATUALIZAR', 'processo', (int) $processo['id']);
        Session::flash('sucesso', 'Dados do processo atualizados.');
        Response::redirect('/processos/' . $processo['id']);
    }

    public function submeter(Request $request, array $params): void
    {
        $processo = $this->carregarDoDono($params);
        $usuario = AuthMiddleware::usuario();

        try {
            WorkflowService::submeter($processo, $usuario);
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
            Response::redirect('/processos/' . $processo['id']);
        }

        Response::redirect('/processos/' . $processo['id'] . '?recibo=1');
    }

    public function cancelar(Request $request, array $params): void
    {
        $processo = $this->carregarDoDono($params);
        $usuario = AuthMiddleware::usuario();

        try {
            WorkflowService::cancelar($processo, $usuario);
            Session::flash('sucesso', 'Processo cancelado.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function responder(Request $request, array $params): void
    {
        $processo = $this->carregarDoDono($params);
        $usuario = AuthMiddleware::usuario();

        if (!ChecklistService::obrigatoriosCompletos($processo)) {
            Session::flash('erro', 'Anexe todos os documentos obrigatórios antes de responder.');
            Response::redirect('/processos/' . $processo['id']);
        }

        try {
            WorkflowService::responderSolicitacao($processo, $usuario);
            Session::flash('sucesso', 'Resposta enviada. O processo voltou à fila do verificador.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function reenviar(Request $request, array $params): void
    {
        $processo = $this->carregarDoDono($params);
        $usuario = AuthMiddleware::usuario();

        try {
            WorkflowService::reenviarRejeitado($processo, $usuario);
            Session::flash('sucesso', 'Processo corrigido e reenviado para verificação.');
        } catch (WorkflowException $e) {
            Session::flash('erro', $e->getMessage());
        }

        Response::redirect('/processos/' . $processo['id']);
    }

    public function dossie(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $processo = ProcessoDocumental::porId($id);
        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }
        $this->garantirVisibilidade($processo, AuthMiddleware::usuario());

        $caminho = DossieService::gerar($processo);
        $nome = 'dossie_' . str_replace('/', '-', $processo['numero_du']) . '.zip';
        Response::download($caminho, $nome);
    }

    public function validarDu(Request $request): void
    {
        $du = trim((string) $request->input('numero_du', ''));
        $valido = valida_numero_du($du);
        $existe = $valido && ProcessoDocumental::porNumeroDu($du) !== null;

        Response::json(['valido' => $valido && !$existe, 'existe' => $existe]);
    }

    public function checklistAjax(Request $request): void
    {
        $categoria = (string) $request->input('categoria', '');
        Response::json(['checklist' => ChecklistService::paraCategoria($categoria)]);
    }

    // ---------------------------------------------------------------

    private function carregarDoDono(array $params): array
    {
        $id = (int) $params['id'];
        $processo = ProcessoDocumental::porId($id);
        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }
        $usuario = AuthMiddleware::usuario();
        if ((int) $processo['despachante_id'] !== (int) $usuario['id']) {
            Response::abort(403, 'Apenas o despachante criador pode executar esta ação.');
        }
        return $processo;
    }

    /**
     * RN14 aplicada também na leitura de um único registo (não só em listagens).
     */
    private function garantirVisibilidade(array $processo, array $usuario): void
    {
        $ok = match ($usuario['perfil']) {
            'despachante' => (int) $processo['despachante_id'] === (int) $usuario['id'],
            'verificador' => (int) ($processo['verificador_id'] ?? 0) === (int) $usuario['id'],
            default => true, // chefe_setor, gestor, administrador, consultor
        };

        if (!$ok) {
            Response::abort(403, 'Não tem permissão para consultar este processo.');
        }
    }
}
