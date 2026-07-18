<?php

declare(strict_types=1);

/**
 * RNF15 — backup diário da base de dados. Agendar via Agendador de Tarefas
 * do Windows (execução diária, ex.: 02:00):
 *   C:\xampp\php\php.exe C:\xampp\htdocs\sagdoc\bin\backup_diario.php
 *
 * Os ficheiros ficam em storage/backups; copie-os periodicamente para uma
 * localização geograficamente separada (rede, cloud, disco externo).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\BackupService;

$basePath = dirname(__DIR__);
Env::load($basePath . '/.env');

try {
    $nome = BackupService::gerar();
    echo date('Y-m-d H:i:s') . " — backup gerado: {$nome}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, date('Y-m-d H:i:s') . ' — falha no backup: ' . $e->getMessage() . "\n");
    exit(1);
}
