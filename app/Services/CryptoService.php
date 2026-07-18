<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use RuntimeException;

/**
 * RNF10 — cifra AES-256-GCM para campos sensíveis, com a chave em APP_KEY
 * (.env). Utilitário disponível para qualquer campo que a DGA venha a
 * classificar como sensível (ex.: dados pessoais adicionais); nenhum campo
 * do esquema atual o exige por si só além do que já é protegido por
 * controlo de acesso (RBAC/RN14) e hashing (senhas).
 */
final class CryptoService
{
    private const CIFRA = 'aes-256-gcm';

    private static function chave(): string
    {
        $base64 = Env::get('APP_KEY', '');
        if ($base64 === '') {
            throw new RuntimeException('APP_KEY não configurada em .env.');
        }
        $chave = base64_decode($base64, true);
        if ($chave === false || strlen($chave) !== 32) {
            throw new RuntimeException('APP_KEY inválida: deve ser 32 bytes em base64.');
        }
        return $chave;
    }

    public static function encriptar(string $texto): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($texto, self::CIFRA, self::chave(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cifrado === false) {
            throw new RuntimeException('Falha ao encriptar dados.');
        }
        return base64_encode($iv . $tag . $cifrado);
    }

    public static function decriptar(string $valor): string
    {
        $dados = base64_decode($valor, true);
        if ($dados === false || strlen($dados) < 28) {
            throw new RuntimeException('Valor cifrado inválido.');
        }
        $iv = substr($dados, 0, 12);
        $tag = substr($dados, 12, 16);
        $cifrado = substr($dados, 28);
        $texto = openssl_decrypt($cifrado, self::CIFRA, self::chave(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($texto === false) {
            throw new RuntimeException('Falha ao decriptar dados (chave incorreta ou dados corrompidos).');
        }
        return $texto;
    }
}
