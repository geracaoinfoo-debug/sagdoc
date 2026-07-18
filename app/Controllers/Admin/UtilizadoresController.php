<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Usuario;
use App\Services\AuditService;

/**
 * RF45 — gestão de utilizadores (criar/editar/desativar, perfis).
 */
final class UtilizadoresController
{
    public function index(Request $request): void
    {
        echo View::render('admin/utilizadores', [
            'tituloPagina' => 'Gestão de Utilizadores',
            'lista' => Usuario::todos(),
        ]);
    }

    public function criar(Request $request): void
    {
        $username = trim((string) $request->input('username', ''));
        $nome = trim((string) $request->input('nome', ''));
        $email = trim((string) $request->input('email', ''));
        $perfil = (string) $request->input('perfil', '');
        $senha = (string) $request->input('senha', '');

        $perfisValidos = ['despachante', 'verificador', 'chefe_setor', 'gestor', 'administrador', 'consultor'];

        if ($username === '' || $nome === '' || !valida_email($email) || !in_array($perfil, $perfisValidos, true) || strlen($senha) < 6) {
            Session::flash('erro', 'Preencha todos os campos corretamente (senha com pelo menos 6 caracteres).');
            Response::redirect('/admin/utilizadores');
        }

        if (Usuario::porUsername($username) || Usuario::porEmail($email)) {
            Session::flash('erro', 'Já existe um utilizador com esse username ou email.');
            Response::redirect('/admin/utilizadores');
        }

        $id = Usuario::criar([
            'username' => $username,
            'password_hash' => password_hash($senha, PASSWORD_BCRYPT),
            'nome' => $nome,
            'email' => $email,
            'telefone' => trim((string) $request->input('telefone', '')) ?: null,
            'perfil' => $perfil,
            'nif' => trim((string) $request->input('nif', '')),
            'numero_licenca' => trim((string) $request->input('numero_licenca', '')),
            'matricula' => trim((string) $request->input('matricula', '')),
            'setor' => trim((string) $request->input('setor', '')) ?: 'Importação',
        ]);

        AuditService::log('USUARIO_CRIAR', 'usuario', $id, "perfil={$perfil}");
        Session::flash('sucesso', 'Utilizador criado com sucesso.');
        Response::redirect('/admin/utilizadores');
    }

    public function atualizar(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $usuario = Usuario::porId($id);
        if (!$usuario) {
            Response::abort(404, 'Utilizador não encontrado.');
        }

        Usuario::atualizar($id, [
            'nome' => trim((string) $request->input('nome', $usuario['nome'])),
            'email' => trim((string) $request->input('email', $usuario['email'])),
            'telefone' => trim((string) $request->input('telefone', '')) ?: null,
            'ativo' => $request->input('ativo') ? 1 : 0,
        ]);

        AuditService::log('USUARIO_ATUALIZAR', 'usuario', $id);
        Session::flash('sucesso', 'Utilizador atualizado.');
        Response::redirect('/admin/utilizadores');
    }
}
