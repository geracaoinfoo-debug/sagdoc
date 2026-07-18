<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\ProcessoDocumental;
use App\Models\Usuario;
use App\Services\ReportService;
use App\Services\SLAService;

final class DashboardController
{
    public function index(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();

        $view = match ($usuario['perfil']) {
            'despachante' => $this->despachante($usuario),
            'verificador' => $this->verificador($usuario),
            'chefe_setor' => $this->chefe($usuario),
            default => $this->gerencial($usuario),
        };

        echo View::render($view['template'], $view['dados'] + ['tituloPagina' => 'Painel']);
    }

    private function despachante(array $usuario): array
    {
        $meus = ProcessoDocumental::paraUsuario($usuario);
        $ativos = array_filter($meus, fn ($p) => !in_array($p['status'], ['aprovado_final', 'cancelado', 'rejeitado'], true));
        $aguardando = array_filter($meus, fn ($p) => in_array($p['status'], ['aguardando_documentos', 'rejeitado'], true));
        $aprovados = array_filter($meus, fn ($p) => $p['status'] === 'aprovado_final');

        return ['template' => 'dashboard/despachante', 'dados' => [
            'usuario' => $usuario,
            'ativos' => count($ativos),
            'aguardando' => count($aguardando),
            'aprovados' => count($aprovados),
            'total' => count($meus),
            'recentes' => array_slice($meus, 0, 8),
        ]];
    }

    private function verificador(array $usuario): array
    {
        $fila = ProcessoDocumental::filaVerificador((int) $usuario['id']);
        $atrasados = array_filter($fila, fn ($p) => SLAService::status($p)['cor'] === 'vermelho');

        $stats = array_values(array_filter(
            ProcessoDocumental::estatisticasVerificadores(),
            fn ($e) => (int) $e['verificador_id'] === (int) $usuario['id']
        ));
        $media = $stats ? round((float) ($stats[0]['tempo_medio_horas'] ?? 0), 1) : 0.0;
        $concluidos = $stats ? (int) $stats[0]['aprovados'] : 0;

        return ['template' => 'dashboard/verificador', 'dados' => [
            'usuario' => $usuario,
            'fila' => $fila,
            'atrasados' => count($atrasados),
            'media' => $media,
            'concluidos' => $concluidos,
        ]];
    }

    private function chefe(array $usuario): array
    {
        $aguardDist = ProcessoDocumental::aguardandoDistribuicao();
        $aguardAprov = ProcessoDocumental::aguardandoAprovacaoFinal();
        $verificadores = ProcessoDocumental::cargaPorVerificador();
        $total = count(ProcessoDocumental::todos());

        return ['template' => 'dashboard/chefe', 'dados' => [
            'usuario' => $usuario,
            'aguardDist' => $aguardDist,
            'aguardAprov' => $aguardAprov,
            'verificadores' => $verificadores,
            'totalProcessos' => $total,
        ]];
    }

    private function gerencial(array $usuario): array
    {
        $kpis = ReportService::kpis();
        $processos = ProcessoDocumental::todos();
        $porStatus = [];
        foreach ($processos as $p) {
            $porStatus[$p['status']] = ($porStatus[$p['status']] ?? 0) + 1;
        }

        return ['template' => 'dashboard/gerencial', 'dados' => [
            'usuario' => $usuario,
            'kpis' => $kpis,
            'porStatus' => $porStatus,
        ]];
    }
}
