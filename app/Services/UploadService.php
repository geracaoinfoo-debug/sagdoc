<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuracao;
use App\Models\Documento;

/**
 * RF09/RN13/§16 — validação real de MIME, extensão e tamanho; ficheiro
 * gravado com nome aleatório fora de qualquer listagem pública direta
 * (public/uploads é bloqueado no .htaccess — RNF26).
 */
final class UploadService
{
    private const EXTENSOES_ACEITES = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MIME_ACEITES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public static function processar(array $arquivo, int $processoId, int $tipoDocumentoId, int $uploadPorId): array
    {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new WorkflowException('Falha no envio do ficheiro (código ' . $arquivo['error'] . ').');
        }

        $tamanhoMaximo = (int) Configuracao::get('tamanho_max_arquivo_mb', 10) * 1024 * 1024;
        if ($arquivo['size'] > $tamanhoMaximo) {
            throw new WorkflowException('Ficheiro excede o tamanho máximo permitido (' . Configuracao::get('tamanho_max_arquivo_mb', 10) . ' MB).');
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, self::EXTENSOES_ACEITES, true)) {
            throw new WorkflowException('Formato de ficheiro não permitido. Utilize PDF, JPG ou PNG.');
        }

        if (!is_uploaded_file($arquivo['tmp_name'])) {
            throw new WorkflowException('Envio de ficheiro inválido.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($arquivo['tmp_name']);
        if (!in_array($mimeReal, self::MIME_ACEITES, true)) {
            throw new WorkflowException('O conteúdo do ficheiro não corresponde a um PDF/JPG/PNG válido.');
        }

        $nomeOriginal = sanitiza_nome_arquivo($arquivo['name']);
        $nomeArmazenado = bin2hex(random_bytes(16)) . '.' . $extensao;

        $pastaBase = dirname(__DIR__, 2) . '/public/uploads/' . $processoId;
        if (!is_dir($pastaBase)) {
            mkdir($pastaBase, 0755, true);
        }

        $destino = $pastaBase . '/' . $nomeArmazenado;
        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new WorkflowException('Não foi possível gravar o ficheiro no servidor.');
        }

        $hash = hash_file('sha256', $destino);

        $documentoId = Documento::criar([
            'processo_id' => $processoId,
            'tipo_documento_id' => $tipoDocumentoId,
            'nome_arquivo' => $nomeOriginal,
            'caminho_arquivo' => $processoId . '/' . $nomeArmazenado,
            'tamanho_bytes' => $arquivo['size'],
            'hash_sha256' => $hash,
            'upload_por' => $uploadPorId,
        ]);

        AuditService::log('DOC_UPLOAD', 'documento', $documentoId, $nomeOriginal . ' (sha256:' . $hash . ')');

        return Documento::porId($documentoId);
    }

    public static function caminhoCompleto(array $documento): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/' . $documento['caminho_arquivo'];
    }
}
