<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ChecklistService;
use PHPUnit\Framework\TestCase;

/**
 * RN02/RF08 — checklist dinâmico por categoria (dados reais de tipos_documentos).
 */
final class ChecklistServiceTest extends TestCase
{
    public function testDocumentosComunsSaoObrigatoriosParaQualquerCategoria(): void
    {
        $checklist = ChecklistService::paraCategoria('Geral');
        $porNome = array_column($checklist, 'obrigatorio', 'nome');

        $this->assertTrue($porNome['Fatura Comercial']);
        $this->assertTrue($porNome['Conhecimento de Embarque (B/L)']);
        $this->assertTrue($porNome['Lista de Embalagem (Packing List)']);
    }

    public function testCertificadoSanitarioSoEhObrigatorioParaAlimentosEAnimais(): void
    {
        $alimentos = ChecklistService::paraCategoria('Alimentos');
        $textil = ChecklistService::paraCategoria('Têxteis');

        $porNomeAlimentos = array_column($alimentos, 'obrigatorio', 'nome');
        $porNomeTextil = array_column($textil, 'obrigatorio', 'nome');

        $this->assertTrue($porNomeAlimentos['Certificado Sanitário']);
        $this->assertFalse($porNomeTextil['Certificado Sanitário']);
    }

    public function testLicencaDeImportacaoEhObrigatoriaParaMedicamentosEQuimicos(): void
    {
        $medicamentos = ChecklistService::paraCategoria('Medicamentos');
        $quimicos = ChecklistService::paraCategoria('Químicos');
        $geral = ChecklistService::paraCategoria('Geral');

        $this->assertTrue(array_column($medicamentos, 'obrigatorio', 'nome')['Licença de Importação']);
        $this->assertTrue(array_column($quimicos, 'obrigatorio', 'nome')['Licença de Importação']);
        $this->assertFalse(array_column($geral, 'obrigatorio', 'nome')['Licença de Importação']);
    }

    public function testCertificadoDeOrigemNuncaEhObrigatorio(): void
    {
        foreach (['Geral', 'Alimentos', 'Medicamentos', 'Veículos'] as $categoria) {
            $checklist = ChecklistService::paraCategoria($categoria);
            $this->assertFalse(array_column($checklist, 'obrigatorio', 'nome')['Certificado de Origem']);
        }
    }
}
