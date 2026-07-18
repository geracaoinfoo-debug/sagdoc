<?php

declare(strict_types=1);

namespace App\Services;

/**
 * RF02 — 2FA opcional para perfis administrativos. Implementação mínima do
 * RFC 6238 (TOTP), sem dependências externas: o segredo é inserido
 * manualmente numa app autenticadora (Google Authenticator, Authy, etc.).
 */
final class TotpService
{
    private const PERIODO = 30;
    private const DIGITOS = 6;

    public static function gerarSegredo(): string
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $segredo = '';
        for ($i = 0; $i < 32; $i++) {
            $segredo .= $alfabeto[random_int(0, 31)];
        }
        return $segredo;
    }

    public static function otpauthUri(string $segredo, string $conta, string $emissor = 'SAGDOC'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($emissor),
            rawurlencode($conta),
            $segredo,
            rawurlencode($emissor),
            self::DIGITOS,
            self::PERIODO
        );
    }

    public static function verificar(string $segredo, string $codigo, int $janela = 1): bool
    {
        $codigo = preg_replace('/\D/', '', $codigo);
        if (strlen($codigo) !== self::DIGITOS) {
            return false;
        }

        $tempoAtual = (int) floor(time() / self::PERIODO);

        for ($i = -$janela; $i <= $janela; $i++) {
            if (hash_equals(self::gerarCodigo($segredo, $tempoAtual + $i), $codigo)) {
                return true;
            }
        }
        return false;
    }

    private static function gerarCodigo(string $segredoBase32, int $contador): string
    {
        $chave = self::base32Decode($segredoBase32);
        $dadosBinarios = pack('N*', 0, $contador);
        $hash = hash_hmac('sha1', $dadosBinarios, $chave, true);
        $offset = ord($hash[19]) & 0x0F;
        $codigo = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITOS);

        return str_pad((string) $codigo, self::DIGITOS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $base32): string
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(rtrim($base32, '='));
        $bits = '';
        foreach (str_split($base32) as $char) {
            $pos = strpos($alfabeto, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int) bindec($byte));
            }
        }
        return $bytes;
    }
}
