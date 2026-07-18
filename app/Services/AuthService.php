<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Middleware\RateLimitMiddleware;
use App\Models\Usuario;

/**
 * RF01-RF05/§16 — autenticação, 2FA opcional e recuperação de senha.
 */
final class AuthService
{
    /**
     * @return array{ok:bool, usuario?:array, requer_totp?:bool, erro?:string}
     */
    public static function tentarLogin(string $username, string $password, string $ip): array
    {
        RateLimitMiddleware::verificar($username, $ip);

        $usuario = Usuario::porUsername($username);

        if (!$usuario || !$usuario['ativo'] || !password_verify($password, $usuario['password_hash'])) {
            RateLimitMiddleware::registar($username, $ip, false);
            AuditService::log('LOGIN_FALHA', 'sessao', $usuario['id'] ?? null, "username={$username}");
            return ['ok' => false, 'erro' => 'Credenciais inválidas. Verifique o utilizador e a senha.'];
        }

        if ($usuario['totp_ativo']) {
            Session::set('_totp_pendente', $usuario['id']);
            return ['ok' => true, 'requer_totp' => true, 'usuario' => $usuario];
        }

        self::concluirLogin($usuario, $ip);

        return ['ok' => true, 'requer_totp' => false, 'usuario' => $usuario];
    }

    public static function confirmarTotp(string $codigo): array
    {
        $usuarioId = Session::get('_totp_pendente');
        if (!$usuarioId) {
            return ['ok' => false, 'erro' => 'Sessão de autenticação expirada. Inicie sessão novamente.'];
        }

        $usuario = Usuario::porId((int) $usuarioId);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $chaveRateLimit = 'totp:' . $usuario['username'];

        RateLimitMiddleware::verificar($chaveRateLimit, $ip);

        if (!$usuario || !TotpService::verificar((string) $usuario['totp_secret'], $codigo)) {
            RateLimitMiddleware::registar($chaveRateLimit, $ip, false);
            AuditService::log('LOGIN_2FA_FALHA', 'sessao', $usuarioId);
            return ['ok' => false, 'erro' => 'Código de verificação inválido.'];
        }

        RateLimitMiddleware::registar($chaveRateLimit, $ip, true);
        Session::remove('_totp_pendente');
        self::concluirLogin($usuario, $ip);

        return ['ok' => true, 'usuario' => $usuario];
    }

    private static function concluirLogin(array $usuario, string $ip): void
    {
        RateLimitMiddleware::registar($usuario['username'], $ip, true);
        Usuario::registarAcesso((int) $usuario['id']);

        Session::regenerate();
        Session::set('usuario', [
            'id' => (int) $usuario['id'],
            'username' => $usuario['username'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'perfil' => $usuario['perfil'],
        ]);

        AuditService::log('LOGIN_OK', 'sessao', (int) $usuario['id'], "perfil={$usuario['perfil']}");
    }

    public static function logout(): void
    {
        AuditService::log('LOGOUT', 'sessao', Session::get('usuario')['id'] ?? null);
        Session::destroy();
    }

    public static function solicitarRecuperacao(string $email): void
    {
        $usuario = Usuario::porEmail($email);
        if (!$usuario) {
            // Não revela se o email existe (evita enumeração de contas).
            return;
        }

        $token = bin2hex(random_bytes(32));
        Usuario::definirResetToken((int) $usuario['id'], $token, date('Y-m-d H:i:s', time() + 3600));

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $link = rtrim($config['url'], '/') . '/redefinir-senha?token=' . $token;

        NotificationService::enviar(
            (int) $usuario['id'],
            'recuperacao_senha',
            'Recuperação de senha',
            "Recebemos um pedido de redefinição de senha. Use o link (válido 1 hora): {$link}",
            null
        );

        AuditService::log('SENHA_RECUPERAR_SOLICITAR', 'usuario', (int) $usuario['id']);
    }

    public static function redefinirSenha(string $token, string $novaSenha): bool
    {
        $usuario = Usuario::porResetToken($token);
        if (!$usuario) {
            return false;
        }

        Usuario::atualizarSenha((int) $usuario['id'], password_hash($novaSenha, PASSWORD_BCRYPT));
        AuditService::log('SENHA_REDEFINIDA', 'usuario', (int) $usuario['id']);

        return true;
    }
}
