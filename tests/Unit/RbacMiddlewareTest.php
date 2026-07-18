<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\RbacMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * RF03/§8 — matriz de permissões RBAC.
 */
final class RbacMiddlewareTest extends TestCase
{
    public function testRotaSemRestricaoDePerfilPermiteQualquerUtilizadorAutenticado(): void
    {
        $this->assertTrue(RbacMiddleware::permitido('despachante', []));
        $this->assertTrue(RbacMiddleware::permitido(null, []));
    }

    public function testPerfilListadoEhPermitido(): void
    {
        $this->assertTrue(RbacMiddleware::permitido('chefe_setor', ['chefe_setor', 'administrador']));
    }

    public function testPerfilNaoListadoEhNegado(): void
    {
        $this->assertFalse(RbacMiddleware::permitido('despachante', ['chefe_setor', 'administrador']));
    }

    public function testSemSessaoEhSempreNegadoQuandoHaRestricao(): void
    {
        $this->assertFalse(RbacMiddleware::permitido(null, ['administrador']));
    }

    /**
     * Espelha a matriz de permissões da §8 para as ações mais sensíveis.
     */
    public function testMatrizDePermissoesParaAcoesCriticas(): void
    {
        $casos = [
            // [perfil, roles-da-rota, esperado]
            ['despachante', ['despachante'], true],       // criar processo
            ['verificador', ['despachante'], false],      // verificador não cria processo
            ['verificador', ['verificador'], true],       // aprovar/rejeitar
            ['chefe_setor', ['chefe_setor', 'administrador'], true], // aprovação final
            ['gestor', ['chefe_setor', 'administrador'], false],    // gestor não aprova
            ['administrador', ['administrador'], true],   // reabrir processo (RN07)
            ['chefe_setor', ['administrador'], false],    // só admin reabre
        ];

        foreach ($casos as [$perfil, $roles, $esperado]) {
            $this->assertSame($esperado, RbacMiddleware::permitido($perfil, $roles), "perfil={$perfil} roles=" . implode(',', $roles));
        }
    }
}
