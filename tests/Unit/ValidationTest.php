<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * RF07 — validação do formato do nº de DU: ^20\d{2}\/\d{6}$
 */
final class ValidationTest extends TestCase
{
    public function testAceitaFormatoValido(): void
    {
        $this->assertTrue(valida_numero_du('2025/001234'));
        $this->assertTrue(valida_numero_du('2099/000001'));
    }

    public function testRejeitaFormatosInvalidos(): void
    {
        $this->assertFalse(valida_numero_du('2025/1234'));      // poucos dígitos
        $this->assertFalse(valida_numero_du('25/001234'));      // ano incompleto
        $this->assertFalse(valida_numero_du('2025-001234'));    // separador errado
        $this->assertFalse(valida_numero_du('1999/001234'));    // fora do padrão 20xx
        $this->assertFalse(valida_numero_du('2025/0012345'));   // dígitos a mais
        $this->assertFalse(valida_numero_du(''));
    }

    public function testValidaNif(): void
    {
        $this->assertTrue(valida_nif('700112233'));
        $this->assertFalse(valida_nif('abc123'));
        $this->assertFalse(valida_nif(''));
    }

    public function testValidaEmail(): void
    {
        $this->assertTrue(valida_email('utilizador@dga.gw'));
        $this->assertFalse(valida_email('sem-arroba.dga.gw'));
    }

    public function testSanitizaNomeArquivoMantemExtensaoERemoveCaracteresPerigosos(): void
    {
        $this->assertSame('script_alert_1.pdf', sanitiza_nome_arquivo('../../script<alert>1.pdf'));
        $this->assertStringEndsWith('.png', sanitiza_nome_arquivo('foto ção.png'));
    }
}
