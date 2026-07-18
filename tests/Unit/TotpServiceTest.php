<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

/**
 * RF02 — 2FA (RFC 6238 TOTP).
 */
final class TotpServiceTest extends TestCase
{
    public function testCodigoGeradoParaOSegredoAtualEValidoNaVerificacao(): void
    {
        $segredo = TotpService::gerarSegredo();

        // Gera o código "oficial" via o mesmo cálculo RFC 6238 usado pela classe
        // (reimplementado aqui de forma independente para não testar a si próprio).
        $codigo = self::codigoDeReferencia($segredo, (int) floor(time() / 30));

        $this->assertTrue(TotpService::verificar($segredo, $codigo));
    }

    public function testCodigoErradoEhRejeitado(): void
    {
        $segredo = TotpService::gerarSegredo();
        $this->assertFalse(TotpService::verificar($segredo, '000000'));
    }

    public function testSegredoTemFormatoBase32DeTrintaEDoisCaracteres(): void
    {
        $segredo = TotpService::gerarSegredo();
        $this->assertSame(32, strlen($segredo));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $segredo);
    }

    private static function codigoDeReferencia(string $segredoBase32, int $contador): string
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(rtrim($segredoBase32, '='));
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

        $dados = pack('N*', 0, $contador);
        $hash = hash_hmac('sha1', $dados, $bytes, true);
        $offset = ord($hash[19]) & 0x0F;
        $codigo = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $codigo, 6, '0', STR_PAD_LEFT);
    }
}
