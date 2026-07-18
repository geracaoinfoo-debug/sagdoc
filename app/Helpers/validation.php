<?php

declare(strict_types=1);

/**
 * RF07: nº DU no formato AAAA/NNNNNN (ex.: 2025/001234).
 */
function valida_numero_du(string $du): bool
{
    return (bool) preg_match('/^20\d{2}\/\d{6}$/', trim($du));
}

function valida_nif(string $nif): bool
{
    return (bool) preg_match('/^\d{6,15}$/', trim($nif));
}

function valida_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitiza um nome de ficheiro para gravação em disco (mantém extensão).
 */
function sanitiza_nome_arquivo(string $nome): string
{
    $info = pathinfo($nome);
    $base = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $info['filename'] ?? 'arquivo');
    $ext = isset($info['extension']) ? '.' . preg_replace('/[^A-Za-z0-9]+/', '', $info['extension']) : '';

    return substr($base, 0, 120) . $ext;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
