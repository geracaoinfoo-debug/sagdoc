<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\Comunicacao;
use App\Models\ProcessoDocumental;
use App\Services\NotificationService;

/**
 * RF27/RF28 — mensagens internas no contexto do processo.
 */
final class MensagemController
{
    public function enviar(Request $request, array $params): void
    {
        $processoId = (int) $params['id'];
        $processo = ProcessoDocumental::porId($processoId);
        $usuario = AuthMiddleware::usuario();

        if (!$processo) {
            Response::abort(404, 'Processo não encontrado.');
        }

        $podeComunicar = in_array((int) $usuario['id'], [(int) $processo['despachante_id'], (int) ($processo['verificador_id'] ?? 0)], true)
            || in_array($usuario['perfil'], ['chefe_setor', 'administrador'], true);

        if (!$podeComunicar) {
            Response::abort(403, 'Sem permissão para comunicar neste processo.');
        }

        $mensagem = trim((string) $request->input('mensagem', ''));
        if ($mensagem === '') {
            Session::flash('erro', 'Escreva uma mensagem antes de enviar.');
            Response::redirect('/processos/' . $processoId);
        }

        $destinatarioId = (int) $usuario['id'] === (int) $processo['despachante_id']
            ? (int) ($processo['verificador_id'] ?? $processo['despachante_id'])
            : (int) $processo['despachante_id'];

        Comunicacao::enviar($processoId, (int) $usuario['id'], $destinatarioId, $mensagem, 'Processo ' . $processo['numero_du']);

        if ($destinatarioId !== (int) $usuario['id']) {
            NotificationService::novaMensagem($processo, $destinatarioId, $usuario['nome']);
        }

        Response::redirect('/processos/' . $processoId);
    }
}
