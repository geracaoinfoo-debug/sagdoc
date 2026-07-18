<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\ProcessoDocumental;

/**
 * RF31/RF32 — pesquisa e filtros combinados, com âmbito por perfil (RN14).
 */
final class ConsultaController
{
    private const CATEGORIAS = ['Alimentos', 'Medicamentos', 'Vegetais', 'Animais', 'Químicos', 'Têxteis', 'Electrónicos', 'Veículos', 'Geral'];
    private const STATUS = ['rascunho', 'submetido', 'aguardando_distribuicao', 'em_verificacao', 'aguardando_documentos', 'aprovado_verificador', 'aprovado_final', 'rejeitado', 'cancelado'];

    public function index(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();

        $filtros = array_filter([
            'numero_du' => trim((string) $request->query('numero_du', '')),
            'importador' => trim((string) $request->query('importador', '')),
            'despachante' => trim((string) $request->query('despachante', '')),
            'status' => (string) $request->query('status', ''),
            'categoria' => (string) $request->query('categoria', ''),
            'data_de' => (string) $request->query('data_de', ''),
            'data_ate' => (string) $request->query('data_ate', ''),
        ]);

        echo View::render('consulta/index', [
            'tituloPagina' => 'Consultar Processos',
            'lista' => ProcessoDocumental::paraUsuario($usuario, $filtros),
            'filtros' => $filtros,
            'categorias' => self::CATEGORIAS,
            'statusDisponiveis' => self::STATUS,
        ]);
    }
}
