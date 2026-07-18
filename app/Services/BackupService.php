<?php

declare(strict_types=1);

namespace App\Services;

/**
 * RF47 — backup e restauração da base de dados. Usa mysqldump/mysql via
 * proc_open com argumentos totalmente escapados (sem shell_exec/passagem de
 * string livre ao shell).
 */
final class BackupService
{
    private static function mysqlBinDir(): string
    {
        // XAMPP em Windows: C:\xampp\mysql\bin. Permite override por variável de ambiente.
        return getenv('MYSQL_BIN_DIR') ?: 'C:\\xampp\\mysql\\bin';
    }

    public static function pastaBackups(): string
    {
        $pasta = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }
        return $pasta;
    }

    public static function gerar(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $binario = self::mysqlBinDir() . DIRECTORY_SEPARATOR . 'mysqldump.exe';

        if (!is_file($binario)) {
            throw new WorkflowException('mysqldump não encontrado em ' . $binario . '.');
        }

        $nomeFicheiro = 'sagdoc_backup_' . date('Y-m-d_His') . '.sql';
        $destino = self::pastaBackups() . DIRECTORY_SEPARATOR . $nomeFicheiro;

        $comando = [
            $binario,
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--routines',
            '--triggers',
            '--single-transaction',
            $config['database'],
        ];

        $descritores = [1 => ['file', $destino, 'w'], 2 => ['pipe', 'w']];
        $env = $config['password'] !== '' ? ['MYSQL_PWD' => $config['password']] : null;
        $processo = proc_open($comando, $descritores, $pipes, null, $env);

        if (!is_resource($processo)) {
            throw new WorkflowException('Não foi possível iniciar o processo de backup.');
        }

        $erro = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $codigo = proc_close($processo);

        if ($codigo !== 0) {
            @unlink($destino);
            throw new WorkflowException('Falha ao gerar backup: ' . trim($erro));
        }

        AuditService::log('BACKUP_GERAR', 'sistema', null, $nomeFicheiro);

        return $nomeFicheiro;
    }

    public static function listar(): array
    {
        $pasta = self::pastaBackups();
        $ficheiros = glob($pasta . '/*.sql') ?: [];
        usort($ficheiros, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map(fn ($f) => [
            'nome' => basename($f),
            'tamanho' => filesize($f),
            'data' => date('Y-m-d H:i:s', filemtime($f)),
        ], $ficheiros);
    }

    public static function caminho(string $nomeFicheiro): ?string
    {
        // Impede path traversal — apenas o nome de ficheiro simples é aceite.
        if (!preg_match('/^sagdoc_backup_[0-9_\-]+\.sql$/', $nomeFicheiro)) {
            return null;
        }
        $caminho = self::pastaBackups() . DIRECTORY_SEPARATOR . $nomeFicheiro;
        return is_file($caminho) ? $caminho : null;
    }
}
