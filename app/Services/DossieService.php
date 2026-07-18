<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;
use App\Models\HistoricoTramitacao;
use ZipArchive;

/**
 * RF34 — download do dossiê completo de um processo em ZIP.
 */
final class DossieService
{
    public static function gerar(array $processo): string
    {
        $documentos = Documento::porProcesso((int) $processo['id']);
        $historico = HistoricoTramitacao::porProcesso((int) $processo['id']);

        $destino = sys_get_temp_dir() . '/sagdoc_dossie_' . $processo['id'] . '_' . bin2hex(random_bytes(4)) . '.zip';

        $zip = new ZipArchive();
        $zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $resumo = "SAGDOC — Dossiê do processo {$processo['numero_du']}\n";
        $resumo .= str_repeat('-', 60) . "\n";
        $resumo .= "Importador: {$processo['importador_nome']} (NIF {$processo['importador_nif']})\n";
        $resumo .= "Despachante: {$processo['despachante_nome']}\n";
        $resumo .= "Categoria: {$processo['categoria']} · Regime: {$processo['regime']}\n";
        $resumo .= "Estado atual: " . status_label($processo['status']) . "\n";
        $resumo .= "Gerado em: " . date('d/m/Y H:i') . "\n\n";
        $resumo .= "HISTÓRICO DE TRAMITAÇÃO\n" . str_repeat('-', 60) . "\n";
        foreach (array_reverse($historico) as $h) {
            $resumo .= '[' . fmt_datahora($h['data_hora']) . '] ' . $h['acao'] . ' — ' . ($h['usuario_nome'] ?? 'Sistema') . "\n";
        }

        $zip->addFromString('resumo_processo.txt', $resumo);

        foreach ($documentos as $doc) {
            $caminho = UploadService::caminhoCompleto($doc);
            if (is_file($caminho)) {
                $zip->addFile($caminho, 'documentos/' . $doc['nome_arquivo']);
            }
        }

        $zip->close();

        AuditService::log('DOC_DOWNLOAD_DOSSIE', 'processo', (int) $processo['id']);

        return $destino;
    }
}
