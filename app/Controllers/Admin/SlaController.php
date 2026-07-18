<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Configuracao;
use App\Services\AuditService;

/**
 * RF44/§12 — configuração de SLA e demais parâmetros de negócio.
 */
final class SlaController
{
    public function index(Request $request): void
    {
        echo View::render('admin/sla', [
            'tituloPagina' => 'Configuração de SLA',
            'config' => Configuracao::todas(),
        ]);
    }

    public function atualizar(Request $request): void
    {
        $campos = [
            'sla_distribuicao_horas', 'sla_verificacao_horas', 'sla_aprovacao_chefe_horas',
            'tamanho_max_arquivo_mb', 'email_notificacoes', 'modo_manutencao',
        ];

        foreach ($campos as $campo) {
            if (in_array($campo, ['email_notificacoes', 'modo_manutencao'], true)) {
                Configuracao::definir($campo, $request->input($campo) ? '1' : '0');
            } elseif ($request->input($campo) !== null) {
                Configuracao::definir($campo, (string) max(1, (int) $request->input($campo)));
            }
        }

        AuditService::log('CONFIG_SLA', 'configuracoes', null);
        Session::flash('sucesso', 'Configuração guardada.');
        Response::redirect('/admin/sla');
    }
}
