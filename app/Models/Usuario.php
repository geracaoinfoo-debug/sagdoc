<?php

declare(strict_types=1);

namespace App\Models;

final class Usuario extends BaseModel
{
    public static function porId(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function porUsername(string $username): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM usuarios WHERE username = :u');
        $stmt->execute(['u' => $username]);
        return $stmt->fetch() ?: null;
    }

    public static function porEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM usuarios WHERE email = :e');
        $stmt->execute(['e' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function porResetToken(string $token): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM usuarios WHERE reset_token = :t AND reset_token_expira > NOW()');
        $stmt->execute(['t' => $token]);
        return $stmt->fetch() ?: null;
    }

    public static function todos(): array
    {
        return self::db()->query('SELECT * FROM usuarios ORDER BY nome')->fetchAll();
    }

    public static function porPerfil(string $perfil): array
    {
        $stmt = self::db()->prepare('SELECT * FROM usuarios WHERE perfil = :p AND ativo = 1 ORDER BY nome');
        $stmt->execute(['p' => $perfil]);
        return $stmt->fetchAll();
    }

    public static function detalhesExtra(int $usuarioId, string $perfil): ?array
    {
        return match ($perfil) {
            'despachante' => self::detalhesDespachante($usuarioId),
            'verificador' => self::detalhesVerificador($usuarioId),
            default => null,
        };
    }

    public static function detalhesDespachante(int $usuarioId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM despachantes WHERE usuario_id = :id');
        $stmt->execute(['id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public static function detalhesVerificador(int $usuarioId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM verificadores WHERE usuario_id = :id');
        $stmt->execute(['id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): int
    {
        $pdo = self::db();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (username, password_hash, nome, email, telefone, perfil, ativo)
             VALUES (:username, :hash, :nome, :email, :telefone, :perfil, :ativo)'
        );
        $stmt->execute([
            'username' => $dados['username'],
            'hash' => $dados['password_hash'],
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'telefone' => $dados['telefone'] ?? null,
            'perfil' => $dados['perfil'],
            'ativo' => $dados['ativo'] ?? 1,
        ]);
        $id = (int) $pdo->lastInsertId();

        if ($dados['perfil'] === 'despachante') {
            $stmt = $pdo->prepare('INSERT INTO despachantes (usuario_id, nif, numero_licenca, data_validade_licenca) VALUES (:id, :nif, :lic, :val)');
            $stmt->execute([
                'id' => $id,
                'nif' => $dados['nif'] ?? '',
                'lic' => $dados['numero_licenca'] ?? '',
                'val' => $dados['data_validade_licenca'] ?? null,
            ]);
        } elseif ($dados['perfil'] === 'verificador') {
            $stmt = $pdo->prepare('INSERT INTO verificadores (usuario_id, matricula, setor) VALUES (:id, :mat, :setor)');
            $stmt->execute([
                'id' => $id,
                'mat' => $dados['matricula'] ?? '',
                'setor' => $dados['setor'] ?? 'Importação',
            ]);
        }

        return $id;
    }

    public static function atualizar(int $id, array $dados): void
    {
        $campos = [];
        $params = ['id' => $id];
        foreach (['nome', 'email', 'telefone', 'perfil', 'ativo'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[] = "$campo = :$campo";
                $params[$campo] = $dados[$campo];
            }
        }
        if ($campos) {
            $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = :id';
            self::db()->prepare($sql)->execute($params);
        }
    }

    public static function atualizarSenha(int $id, string $hash): void
    {
        self::db()->prepare('UPDATE usuarios SET password_hash = :h, reset_token = NULL, reset_token_expira = NULL WHERE id = :id')
            ->execute(['h' => $hash, 'id' => $id]);
    }

    public static function definirResetToken(int $id, string $token, string $expira): void
    {
        self::db()->prepare('UPDATE usuarios SET reset_token = :t, reset_token_expira = :e WHERE id = :id')
            ->execute(['t' => $token, 'e' => $expira, 'id' => $id]);
    }

    public static function registarAcesso(int $id): void
    {
        self::db()->prepare('UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public static function definirTotp(int $id, ?string $secret, bool $ativo): void
    {
        self::db()->prepare('UPDATE usuarios SET totp_secret = :s, totp_ativo = :a WHERE id = :id')
            ->execute(['s' => $secret, 'a' => $ativo ? 1 : 0, 'id' => $id]);
    }

    public static function contarPorPerfil(): array
    {
        return self::db()->query('SELECT perfil, COUNT(*) AS total FROM usuarios GROUP BY perfil')->fetchAll();
    }
}
