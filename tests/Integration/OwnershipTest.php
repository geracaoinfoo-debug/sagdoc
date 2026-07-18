<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\ProcessoDocumental;
use App\Models\Usuario;
use PHPUnit\Framework\TestCase;

/**
 * RN14 — acesso por propriedade: despachante vê os seus; verificador os
 * atribuídos; chefe/gestor/administrador veem todos.
 */
final class OwnershipTest extends TestCase
{
    public function testDespachanteSoVeOsProprios(): void
    {
        $jbarbosa = Usuario::porUsername('jbarbosa');
        $mcande = Usuario::porUsername('mcande');

        $listaJbarbosa = ProcessoDocumental::paraUsuario($jbarbosa);
        foreach ($listaJbarbosa as $p) {
            $this->assertSame((int) $jbarbosa['id'], (int) $p['despachante_id']);
        }

        $listaMcande = ProcessoDocumental::paraUsuario($mcande);
        foreach ($listaMcande as $p) {
            $this->assertSame((int) $mcande['id'], (int) $p['despachante_id']);
        }

        // Confirma que os conjuntos são de facto diferentes (não é um "vê tudo" disfarçado).
        $duJbarbosa = array_column($listaJbarbosa, 'numero_du');
        $duMcande = array_column($listaMcande, 'numero_du');
        $this->assertEmpty(array_intersect($duJbarbosa, $duMcande));
    }

    public function testVerificadorSoVeOsAtribuidosASi(): void
    {
        $averificador = Usuario::porUsername('averificador');
        $lista = ProcessoDocumental::paraUsuario($averificador);

        foreach ($lista as $p) {
            $this->assertSame((int) $averificador['id'], (int) $p['verificador_id']);
        }
    }

    public function testGestorVeTodosOsProcessos(): void
    {
        $gestor = Usuario::porUsername('gestor');
        $lista = ProcessoDocumental::paraUsuario($gestor);
        $total = ProcessoDocumental::todos();

        $this->assertCount(count($total), $lista);
    }
}
