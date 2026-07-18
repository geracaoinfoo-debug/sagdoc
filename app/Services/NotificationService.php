<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuracao;
use App\Models\Notificacao;
use App\Models\Usuario;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * RF29/RF30/RN15/§11 — cada evento gera notificação in-app e, se ativado,
 * email (PHPMailer). Sem SMTP configurado, o email é escrito em
 * storage/logs/mail.log em vez de enviado (modo de desenvolvimento).
 */
final class NotificationService
{
    public static function enviar(int $usuarioId, string $tipo, string $titulo, string $mensagem, ?string $link = null): void
    {
        Notificacao::criar($usuarioId, $tipo, $titulo, $mensagem, $link);

        if (!Configuracao::get('email_notificacoes', true)) {
            return;
        }

        $usuario = Usuario::porId($usuarioId);
        if (!$usuario) {
            return;
        }

        self::enviarEmail($usuario['email'], $usuario['nome'], $titulo, $mensagem);
    }

    private static function enviarEmail(string $destinatarioEmail, string $destinatarioNome, string $assunto, string $corpo): void
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';

        if (empty($config['host'])) {
            self::registarStub($destinatarioEmail, $assunto, $corpo);
            return;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPAuth = $config['username'] !== '';
            if ($mail->SMTPAuth) {
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['from'], $config['from_name']);
            $mail->addAddress($destinatarioEmail, $destinatarioNome);
            $mail->Subject = '[SAGDOC] ' . $assunto;
            $mail->Body = $corpo;
            $mail->send();
        } catch (Throwable $e) {
            self::registarStub($destinatarioEmail, $assunto, $corpo . "\n[Falha no envio: {$e->getMessage()}]");
        }
    }

    private static function registarStub(string $destinatario, string $assunto, string $corpo): void
    {
        $linha = sprintf(
            "[%s] PARA: %s | ASSUNTO: %s | CORPO: %s\n",
            date('Y-m-d H:i:s'),
            $destinatario,
            $assunto,
            str_replace(["\r", "\n"], ' ', $corpo)
        );
        @file_put_contents(dirname(__DIR__, 2) . '/storage/logs/mail.log', $linha, FILE_APPEND);
    }

    // ---------------------------------------------------------------
    // Matriz evento → destinatário (§11)
    // ---------------------------------------------------------------

    public static function processoDistribuido(array $processo): void
    {
        self::enviar(
            (int) $processo['despachante_id'],
            'distribuicao',
            'Processo distribuído',
            "O processo {$processo['numero_du']} foi distribuído para verificação.",
            '/processos/' . $processo['id']
        );
        self::enviar(
            (int) $processo['verificador_id'],
            'atribuicao',
            'Novo processo na sua fila',
            "O processo {$processo['numero_du']} foi-lhe atribuído para verificação.",
            '/processos/' . $processo['id']
        );
    }

    public static function documentosSolicitados(array $processo, string $motivo): void
    {
        self::enviar(
            (int) $processo['despachante_id'],
            'solicitacao',
            'Documentos adicionais solicitados',
            "No processo {$processo['numero_du']}: {$motivo}",
            '/processos/' . $processo['id']
        );
    }

    public static function despachanteRespondeu(array $processo): void
    {
        if (!$processo['verificador_id']) {
            return;
        }
        self::enviar(
            (int) $processo['verificador_id'],
            'resposta',
            'Despachante respondeu à solicitação',
            "O processo {$processo['numero_du']} foi atualizado com novos documentos e voltou à sua fila.",
            '/processos/' . $processo['id']
        );
    }

    public static function aprovadoPeloVerificador(array $processo, array $chefes): void
    {
        foreach ($chefes as $chefe) {
            self::enviar(
                (int) $chefe['id'],
                'aprovacao',
                'Processo aguarda aprovação final',
                "O processo {$processo['numero_du']} foi aprovado tecnicamente e aguarda aprovação final.",
                '/processos/' . $processo['id']
            );
        }
        self::enviar(
            (int) $processo['despachante_id'],
            'aprovacao',
            'Processo aprovado pelo verificador',
            "O processo {$processo['numero_du']} foi aprovado tecnicamente e aguarda aprovação final.",
            '/processos/' . $processo['id']
        );
    }

    public static function aprovacaoFinal(array $processo): void
    {
        self::enviar(
            (int) $processo['despachante_id'],
            'aprovacao_final',
            'Aprovação final concedida',
            "O processo {$processo['numero_du']} foi aprovado e concluído.",
            '/processos/' . $processo['id']
        );
        if ($processo['verificador_id']) {
            self::enviar(
                (int) $processo['verificador_id'],
                'aprovacao_final',
                'Processo aprovado pelo Chefe de Setor',
                "O processo {$processo['numero_du']} recebeu aprovação final.",
                '/processos/' . $processo['id']
            );
        }
    }

    public static function rejeitado(array $processo, string $motivo): void
    {
        self::enviar(
            (int) $processo['despachante_id'],
            'rejeicao',
            'Processo rejeitado',
            "O processo {$processo['numero_du']} foi rejeitado: {$motivo}",
            '/processos/' . $processo['id']
        );
    }

    public static function devolvido(array $processo, string $motivo): void
    {
        if (!$processo['verificador_id']) {
            return;
        }
        self::enviar(
            (int) $processo['verificador_id'],
            'devolucao',
            'Processo devolvido pelo Chefe',
            "O processo {$processo['numero_du']} foi devolvido para revisão: {$motivo}",
            '/processos/' . $processo['id']
        );
    }

    public static function slaUltrapassado(array $processo, array $chefes): void
    {
        foreach ($chefes as $chefe) {
            self::enviar(
                (int) $chefe['id'],
                'sla',
                'SLA ultrapassado',
                "O processo {$processo['numero_du']} ultrapassou o prazo de SLA da fase atual.",
                '/processos/' . $processo['id']
            );
        }
    }

    public static function novaMensagem(array $processo, int $destinatarioId, string $remetenteNome): void
    {
        self::enviar(
            $destinatarioId,
            'mensagem',
            'Nova mensagem',
            "{$remetenteNome} enviou uma mensagem no processo {$processo['numero_du']}.",
            '/processos/' . $processo['id']
        );
    }
}
