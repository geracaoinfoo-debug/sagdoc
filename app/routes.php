<?php

declare(strict_types=1);

use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\LogsController;
use App\Controllers\Admin\SlaController;
use App\Controllers\Admin\TiposDocumentosController;
use App\Controllers\Admin\UtilizadoresController;
use App\Controllers\AprovacaoFinalController;
use App\Controllers\AuthController;
use App\Controllers\ConsultaController;
use App\Controllers\DashboardController;
use App\Controllers\DistribuicaoController;
use App\Controllers\DocumentoController;
use App\Controllers\MensagemController;
use App\Controllers\NotificacaoController;
use App\Controllers\ProcessoController;
use App\Controllers\RelatorioController;
use App\Controllers\VerificacaoController;
use App\Core\Router;

return function (Router $router): void {
    // -----------------------------------------------------------------
    // Autenticação (RF01-RF05) — sem sessão exigida
    // -----------------------------------------------------------------
    $router->get('/', [AuthController::class, 'loginForm'], ['auth' => false]);
    $router->get('/login', [AuthController::class, 'loginForm'], ['auth' => false]);
    $router->post('/login', [AuthController::class, 'login'], ['auth' => false]);
    $router->get('/login/totp', [AuthController::class, 'totpForm'], ['auth' => false]);
    $router->post('/login/totp', [AuthController::class, 'totpVerify'], ['auth' => false]);
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->get('/recuperar-senha', [AuthController::class, 'recuperarForm'], ['auth' => false]);
    $router->post('/recuperar-senha', [AuthController::class, 'recuperarEnviar'], ['auth' => false]);
    $router->get('/redefinir-senha', [AuthController::class, 'redefinirForm'], ['auth' => false]);
    $router->post('/redefinir-senha', [AuthController::class, 'redefinirSalvar'], ['auth' => false]);
    $router->get('/perfil/2fa', [AuthController::class, 'totpAtivarForm'], ['roles' => ['administrador', 'gestor', 'chefe_setor']]);
    $router->post('/perfil/2fa/confirmar', [AuthController::class, 'totpAtivarConfirmar'], ['roles' => ['administrador', 'gestor', 'chefe_setor']]);
    $router->post('/perfil/2fa/desativar', [AuthController::class, 'totpDesativar'], ['roles' => ['administrador', 'gestor', 'chefe_setor']]);

    // -----------------------------------------------------------------
    // Dashboard (RF36-RF39)
    // -----------------------------------------------------------------
    $router->get('/dashboard', [DashboardController::class, 'index']);

    // -----------------------------------------------------------------
    // Processos (RF06-RF14, RF33-RF35)
    // -----------------------------------------------------------------
    $router->get('/processos', [ProcessoController::class, 'index'], ['roles' => ['despachante']]);
    $router->get('/processos/novo', [ProcessoController::class, 'novoForm'], ['roles' => ['despachante']]);
    $router->post('/processos', [ProcessoController::class, 'criar'], ['roles' => ['despachante']]);
    $router->get('/processos/{id}', [ProcessoController::class, 'detalhe']);
    $router->post('/processos/{id}/atualizar', [ProcessoController::class, 'atualizar'], ['roles' => ['despachante']]);
    $router->post('/processos/{id}/submeter', [ProcessoController::class, 'submeter'], ['roles' => ['despachante']]);
    $router->post('/processos/{id}/cancelar', [ProcessoController::class, 'cancelar'], ['roles' => ['despachante']]);
    $router->post('/processos/{id}/responder', [ProcessoController::class, 'responder'], ['roles' => ['despachante']]);
    $router->post('/processos/{id}/reenviar', [ProcessoController::class, 'reenviar'], ['roles' => ['despachante']]);
    $router->get('/processos/{id}/dossie.zip', [ProcessoController::class, 'dossie']);
    $router->post('/api/validar-du', [ProcessoController::class, 'validarDu'], ['roles' => ['despachante']]);
    $router->post('/api/checklist', [ProcessoController::class, 'checklistAjax'], ['roles' => ['despachante']]);

    // -----------------------------------------------------------------
    // Documentos (RF09-RF12, RF18)
    // -----------------------------------------------------------------
    $router->post('/processos/{id}/documentos', [DocumentoController::class, 'upload'], ['roles' => ['despachante']]);
    $router->delete('/processos/{id}/documentos/{docId}', [DocumentoController::class, 'remover'], ['roles' => ['despachante']]);
    $router->get('/documentos/{id}', [DocumentoController::class, 'download']);
    $router->post('/documentos/{id}/marcar-verificado', [DocumentoController::class, 'marcarVerificado'], ['roles' => ['verificador']]);

    // -----------------------------------------------------------------
    // Distribuição (RF15-RF17)
    // -----------------------------------------------------------------
    $router->get('/distribuicao', [DistribuicaoController::class, 'index'], ['roles' => ['chefe_setor', 'administrador']]);
    $router->post('/distribuicao/automatica', [DistribuicaoController::class, 'automatica'], ['roles' => ['chefe_setor', 'administrador']]);
    $router->post('/processos/{id}/distribuir', [DistribuicaoController::class, 'manual'], ['roles' => ['chefe_setor', 'administrador']]);
    $router->post('/processos/{id}/reatribuir', [DistribuicaoController::class, 'reatribuirRejeitado'], ['roles' => ['chefe_setor', 'administrador']]);

    // -----------------------------------------------------------------
    // Verificação (RF18-RF24)
    // -----------------------------------------------------------------
    $router->get('/fila', [VerificacaoController::class, 'fila'], ['roles' => ['verificador']]);
    $router->post('/processos/{id}/aprovar', [VerificacaoController::class, 'aprovar'], ['roles' => ['verificador']]);
    $router->post('/processos/{id}/solicitar-docs', [VerificacaoController::class, 'solicitarDocs'], ['roles' => ['verificador']]);
    $router->post('/processos/{id}/rejeitar', [VerificacaoController::class, 'rejeitar'], ['roles' => ['verificador']]);

    // -----------------------------------------------------------------
    // Aprovação final (RF25) + reabertura (RN07)
    // -----------------------------------------------------------------
    $router->get('/aprovacao-final', [AprovacaoFinalController::class, 'index'], ['roles' => ['chefe_setor', 'administrador']]);
    $router->post('/processos/{id}/aprovar-final', [AprovacaoFinalController::class, 'aprovar'], ['roles' => ['chefe_setor', 'administrador']]);
    $router->post('/processos/{id}/devolver', [AprovacaoFinalController::class, 'devolver'], ['roles' => ['chefe_setor', 'administrador']]);
    $router->post('/processos/{id}/reabrir', [AprovacaoFinalController::class, 'reabrir'], ['roles' => ['administrador']]);

    // -----------------------------------------------------------------
    // Comunicações (RF27-RF28)
    // -----------------------------------------------------------------
    $router->post('/processos/{id}/mensagens', [MensagemController::class, 'enviar']);

    // -----------------------------------------------------------------
    // Notificações (RF30)
    // -----------------------------------------------------------------
    $router->get('/notificacoes', [NotificacaoController::class, 'index']);
    $router->get('/notificacoes/lista', [NotificacaoController::class, 'listaJson']);
    $router->post('/notificacoes/{id}/ler', [NotificacaoController::class, 'marcarLida']);

    // -----------------------------------------------------------------
    // Consulta e pesquisa (RF31-RF32)
    // -----------------------------------------------------------------
    $router->get('/consulta', [ConsultaController::class, 'index']);

    // -----------------------------------------------------------------
    // Relatórios & KPIs (RF40-RF41)
    // -----------------------------------------------------------------
    $router->get('/relatorios', [RelatorioController::class, 'index'], ['roles' => ['chefe_setor', 'gestor', 'administrador', 'consultor']]);
    $router->get('/relatorios/export', [RelatorioController::class, 'exportar'], ['roles' => ['chefe_setor', 'gestor', 'administrador', 'consultor']]);

    // -----------------------------------------------------------------
    // Administração (RF42-RF47)
    // -----------------------------------------------------------------
    $router->get('/admin/utilizadores', [UtilizadoresController::class, 'index'], ['roles' => ['administrador']]);
    $router->post('/admin/utilizadores', [UtilizadoresController::class, 'criar'], ['roles' => ['administrador']]);
    $router->post('/admin/utilizadores/{id}', [UtilizadoresController::class, 'atualizar'], ['roles' => ['administrador']]);

    $router->get('/admin/tipos-documentos', [TiposDocumentosController::class, 'index'], ['roles' => ['administrador']]);
    $router->post('/admin/tipos-documentos', [TiposDocumentosController::class, 'criar'], ['roles' => ['administrador']]);
    $router->post('/admin/tipos-documentos/{id}', [TiposDocumentosController::class, 'atualizar'], ['roles' => ['administrador']]);

    $router->get('/admin/sla', [SlaController::class, 'index'], ['roles' => ['administrador']]);
    $router->post('/admin/sla', [SlaController::class, 'atualizar'], ['roles' => ['administrador']]);

    $router->get('/admin/logs', [LogsController::class, 'index'], ['roles' => ['administrador']]);

    $router->get('/admin/backup', [BackupController::class, 'index'], ['roles' => ['administrador']]);
    $router->post('/admin/backup/gerar', [BackupController::class, 'gerar'], ['roles' => ['administrador']]);
    $router->get('/admin/backup/download/{ficheiro}', [BackupController::class, 'download'], ['roles' => ['administrador']]);
};
