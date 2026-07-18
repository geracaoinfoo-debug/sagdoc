<?php

declare(strict_types=1);

function fmt_datahora(?string $dataHora): string
{
    if (!$dataHora) {
        return '—';
    }

    return (new DateTime($dataHora))->format('d/m/Y H:i');
}

function fmt_data(?string $data): string
{
    if (!$data) {
        return '—';
    }

    return (new DateTime($data))->format('d/m/Y');
}

function fmt_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function status_label(string $status): string
{
    static $labels = [
        'rascunho' => 'Rascunho',
        'submetido' => 'Submetido',
        'aguardando_distribuicao' => 'Aguardando Distribuição',
        'em_verificacao' => 'Em Verificação',
        'aguardando_documentos' => 'Aguardando Documentos',
        'aprovado_verificador' => 'Aprovado p/ Verificador',
        'aprovado_final' => 'Aprovado Final',
        'rejeitado' => 'Rejeitado',
        'cancelado' => 'Cancelado',
    ];

    return $labels[$status] ?? $status;
}

function perfil_label(string $perfil): string
{
    static $labels = [
        'despachante' => 'Despachante',
        'verificador' => 'Verificador Aduaneiro',
        'chefe_setor' => 'Chefe de Setor',
        'gestor' => 'Gestor DGA',
        'administrador' => 'Administrador',
        'consultor' => 'Consultor',
    ];

    return $labels[$perfil] ?? $perfil;
}
