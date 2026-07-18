<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;
use App\Models\TipoDocumento;

/**
 * RF08/RN02 — checklist dinâmico de documentos por categoria de mercadoria.
 */
final class ChecklistService
{
    public static function paraCategoria(string $categoria): array
    {
        return TipoDocumento::checklistParaCategoria($categoria);
    }

    /**
     * Cruza o checklist da categoria do processo com os documentos já anexados.
     */
    public static function estadoDoProcesso(array $processo): array
    {
        $checklist = self::paraCategoria($processo['categoria']);
        $documentos = Documento::porProcesso((int) $processo['id']);

        foreach ($checklist as &$item) {
            $item['documentos'] = array_values(array_filter(
                $documentos,
                fn ($d) => (int) $d['tipo_documento_id'] === $item['id']
            ));
            $item['enviado'] = count($item['documentos']) > 0;
        }
        unset($item);

        return $checklist;
    }

    /**
     * RN03 — só permite submeter se todos os documentos obrigatórios estiverem anexados.
     */
    public static function obrigatoriosCompletos(array $processo): bool
    {
        foreach (self::estadoDoProcesso($processo) as $item) {
            if ($item['obrigatorio'] && !$item['enviado']) {
                return false;
            }
        }
        return true;
    }

    public static function documentosEmFalta(array $processo): array
    {
        return array_values(array_filter(
            self::estadoDoProcesso($processo),
            fn ($item) => $item['obrigatorio'] && !$item['enviado']
        ));
    }
}
