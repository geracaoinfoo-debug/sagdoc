<?php

declare(strict_types=1);

/**
 * RF26/RN15/§10 — reavaliação periódica de SLA. Agendar via Agendador de
 * Tarefas do Windows (ex.: a cada 30 minutos):
 *   C:\xampp\php\php.exe C:\xampp\htdocs\sagdoc\bin\sla_check.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Models\Notificacao;
use App\Models\ProcessoDocumental;
use App\Models\Usuario;
use App\Services\NotificationService;
use App\Services\SLAService;

$basePath = dirname(__DIR__);
Env::load($basePath . '/.env');

$processos = ProcessoDocumental::todos();
$ultrapassados = SLAService::ultrapassados($processos);
$chefes = Usuario::porPerfil('chefe_setor');

$notificados = 0;
foreach ($ultrapassados as $processo) {
    $sla = SLAService::status($processo);
    $link = '/processos/' . $processo['id'];
    // Já alertado nesta mesma fase (desde que entrou no estado atual)? Não repete.
    if ($sla['desde'] !== null && Notificacao::existeDesde('sla', $link, $sla['desde'])) {
        continue;
    }
    NotificationService::slaUltrapassado($processo, $chefes);
    $notificados++;
}

echo date('Y-m-d H:i:s') . " — {$notificados} processo(s) com SLA ultrapassado notificado(s).\n";
