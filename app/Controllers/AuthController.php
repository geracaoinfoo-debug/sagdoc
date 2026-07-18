<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\TotpService;

final class AuthController
{
    public function loginForm(Request $request): void
    {
        if (AuthMiddleware::usuario() !== null) {
            Response::redirect('/dashboard');
        }
        echo View::render('auth/login', ['tituloPagina' => 'Autenticação'], 'auth');
    }

    public function login(Request $request): void
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            Session::flash('erro', 'Indique o utilizador e a senha.');
            Response::redirect('/login');
        }

        $resultado = AuthService::tentarLogin($username, $password, $request->ip());

        if (!$resultado['ok']) {
            Session::flash('erro', $resultado['erro']);
            Response::redirect('/login');
        }

        if ($resultado['requer_totp'] ?? false) {
            Response::redirect('/login/totp');
        }

        $destino = Session::get('_redirect_after_login', '/dashboard');
        Session::remove('_redirect_after_login');
        Response::redirect($destino);
    }

    public function totpForm(Request $request): void
    {
        if (!Session::has('_totp_pendente')) {
            Response::redirect('/login');
        }
        echo View::render('auth/totp', ['tituloPagina' => 'Verificação em duas etapas'], 'auth');
    }

    public function totpVerify(Request $request): void
    {
        $resultado = AuthService::confirmarTotp((string) $request->input('codigo', ''));

        if (!$resultado['ok']) {
            Session::flash('erro', $resultado['erro']);
            Response::redirect('/login/totp');
        }

        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        AuthService::logout();
        Response::redirect('/login');
    }

    public function recuperarForm(Request $request): void
    {
        echo View::render('auth/recuperar', ['tituloPagina' => 'Recuperar senha'], 'auth');
    }

    public function recuperarEnviar(Request $request): void
    {
        $email = trim((string) $request->input('email', ''));
        if (valida_email($email)) {
            AuthService::solicitarRecuperacao($email);
        }
        Session::flash('sucesso', 'Se o email indicado existir no sistema, enviámos um link de redefinição (válido 1 hora).');
        Response::redirect('/login');
    }

    public function redefinirForm(Request $request): void
    {
        $token = (string) $request->query('token', '');
        echo View::render('auth/redefinir', ['tituloPagina' => 'Redefinir senha', 'token' => $token], 'auth');
    }

    public function redefinirSalvar(Request $request): void
    {
        $token = (string) $request->input('token', '');
        $senha = (string) $request->input('senha', '');
        $confirmacao = (string) $request->input('confirmacao', '');

        if (strlen($senha) < 6 || $senha !== $confirmacao) {
            Session::flash('erro', 'As senhas têm de coincidir e ter pelo menos 6 caracteres.');
            Response::redirect('/redefinir-senha?token=' . urlencode($token));
        }

        if (!AuthService::redefinirSenha($token, $senha)) {
            Session::flash('erro', 'Link de redefinição inválido ou expirado.');
            Response::redirect('/recuperar-senha');
        }

        Session::flash('sucesso', 'Senha redefinida com sucesso. Inicie sessão.');
        Response::redirect('/login');
    }

    /**
     * RF02 — ativação de 2FA (perfis administrativos), acedido a partir do perfil do utilizador autenticado.
     */
    public function totpAtivarForm(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        $segredo = Session::get('_totp_setup_secret');
        if (!$segredo) {
            $segredo = TotpService::gerarSegredo();
            Session::set('_totp_setup_secret', $segredo);
        }
        echo View::render('auth/totp_setup', [
            'tituloPagina' => 'Ativar verificação em duas etapas',
            'segredo' => $segredo,
            'otpauth' => TotpService::otpauthUri($segredo, $usuario['username']),
        ]);
    }

    public function totpAtivarConfirmar(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        $segredo = (string) Session::get('_totp_setup_secret', '');
        $codigo = (string) $request->input('codigo', '');

        if ($segredo === '' || !TotpService::verificar($segredo, $codigo)) {
            Session::flash('erro', 'Código inválido. Tente novamente.');
            Response::redirect('/perfil/2fa');
        }

        \App\Models\Usuario::definirTotp((int) $usuario['id'], $segredo, true);
        Session::remove('_totp_setup_secret');
        Session::flash('sucesso', 'Verificação em duas etapas ativada com sucesso.');
        Response::redirect('/dashboard');
    }

    public function totpDesativar(Request $request): void
    {
        $usuario = AuthMiddleware::usuario();
        \App\Models\Usuario::definirTotp((int) $usuario['id'], null, false);
        Session::flash('sucesso', 'Verificação em duas etapas desativada.');
        Response::redirect('/dashboard');
    }
}
