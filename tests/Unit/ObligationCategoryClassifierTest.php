<?php

namespace Tests\Unit;

use App\Services\ObligationCategoryClassifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ObligationCategoryClassifierTest extends TestCase
{
    // ── isValid / sanitize ────────────────────────────────────────────────────

    #[Test]
    public function valid_category_passes_isValid(): void
    {
        foreach (ObligationCategoryClassifier::CATEGORIES as $cat) {
            $this->assertTrue(ObligationCategoryClassifier::isValid($cat), "Expected '{$cat}' to be valid");
        }
    }

    #[Test]
    public function invalid_category_fails_isValid(): void
    {
        $this->assertFalse(ObligationCategoryClassifier::isValid(null));
        $this->assertFalse(ObligationCategoryClassifier::isValid(''));
        $this->assertFalse(ObligationCategoryClassifier::isValid('Cobrança'));
        $this->assertFalse(ObligationCategoryClassifier::isValid('relatório')); // wrong case
    }

    #[Test]
    public function sanitize_returns_valid_category_unchanged(): void
    {
        $this->assertSame('Covenants', ObligationCategoryClassifier::sanitize('Covenants'));
        $this->assertSame('Outro', ObligationCategoryClassifier::sanitize('Outro'));
    }

    #[Test]
    public function sanitize_maps_unknown_value_to_Outro(): void
    {
        $this->assertSame('Outro', ObligationCategoryClassifier::sanitize(null));
        $this->assertSame('Outro', ObligationCategoryClassifier::sanitize('Nenhum'));
        $this->assertSame('Outro', ObligationCategoryClassifier::sanitize(''));
    }

    // ── classifyFromTypeAndText ───────────────────────────────────────────────

    #[Test]
    public function classifies_fundo_keywords_as_Fundos(): void
    {
        $this->assertSame('Fundos', ObligationCategoryClassifier::classifyFromTypeAndText('Controle de Fundo de Reserva'));
        $this->assertSame('Fundos', ObligationCategoryClassifier::classifyFromTypeAndText('Controle de Fundo de Obra'));
        $this->assertSame('Fundos', ObligationCategoryClassifier::classifyFromTypeAndText('Controle de Fundo de Despesas'));
    }

    #[Test]
    public function classifies_covenant_as_Covenants(): void
    {
        $this->assertSame('Covenants', ObligationCategoryClassifier::classifyFromTypeAndText('Covenant Financeiro'));
        $this->assertSame('Covenants', ObligationCategoryClassifier::classifyFromTypeAndText('Covenant Operacional'));
    }

    #[Test]
    public function classifies_relatorio_as_Informacional(): void
    {
        $this->assertSame('Informacional', ObligationCategoryClassifier::classifyFromTypeAndText('Relatório Periódico'));
        $this->assertSame('Informacional', ObligationCategoryClassifier::classifyFromTypeAndText('Demonstrações Financeiras'));
        $this->assertSame('Informacional', ObligationCategoryClassifier::classifyFromTypeAndText('Comunicação a Investidores'));
    }

    #[Test]
    public function classifies_garantia_as_Garantias(): void
    {
        $this->assertSame('Garantias', ObligationCategoryClassifier::classifyFromTypeAndText('Monitoramento de Garantias'));
    }

    #[Test]
    public function classifies_recebiveis_as_Recebiveis_Lastro(): void
    {
        $this->assertSame('Recebíveis / Lastro', ObligationCategoryClassifier::classifyFromTypeAndText('Monitoramento de Recebíveis'));
    }

    #[Test]
    public function classifies_obra_keywords_as_Obras(): void
    {
        $this->assertSame('Obras', ObligationCategoryClassifier::classifyFromTypeAndText('Relatório de Medição de Obra'));
    }

    #[Test]
    public function classifies_vencimento_antecipado_as_VencimentoAntecipado(): void
    {
        $this->assertSame('Vencimento Antecipado', ObligationCategoryClassifier::classifyFromTypeAndText('Evento de Vencimento Antecipado'));
        $this->assertSame('Vencimento Antecipado', ObligationCategoryClassifier::classifyFromTypeAndText('Evento de Inadimplemento'));
    }

    #[Test]
    public function classifies_patrimonio_separado_as_PatrimonioSeparado(): void
    {
        $this->assertSame('Patrimônio Separado', ObligationCategoryClassifier::classifyFromTypeAndText('Acompanhamento do Patrimônio Separado'));
    }

    #[Test]
    public function classifies_assembleia_waiver_correctly(): void
    {
        $this->assertSame('Assembleia / Waiver', ObligationCategoryClassifier::classifyFromTypeAndText(null, 'Convocação de Assembleia Geral'));
        $this->assertSame('Assembleia / Waiver', ObligationCategoryClassifier::classifyFromTypeAndText(null, 'Solicitação de Waiver'));
    }

    #[Test]
    public function classifies_regulatoria_correctly(): void
    {
        $this->assertSame('Regulatória', ObligationCategoryClassifier::classifyFromTypeAndText(null, 'Comunicação à CVM'));
        $this->assertSame('Regulatória', ObligationCategoryClassifier::classifyFromTypeAndText(null, 'Registro na B3'));
    }

    #[Test]
    public function classifies_pagamento_as_FinanceiraPagamento(): void
    {
        $this->assertSame('Financeira / Pagamento', ObligationCategoryClassifier::classifyFromTypeAndText(null, 'Amortização mensal dos CRI'));
        $this->assertSame('Financeira / Pagamento', ObligationCategoryClassifier::classifyFromTypeAndText(null, null, 'Pagamento de tributos devidos'));
    }

    #[Test]
    public function falls_back_to_Outro_when_no_keywords_match(): void
    {
        $this->assertSame('Outro', ObligationCategoryClassifier::classifyFromTypeAndText(null, null, null, null));
        $this->assertSame('Outro', ObligationCategoryClassifier::classifyFromTypeAndText('Outro'));
        $this->assertSame('Outro', ObligationCategoryClassifier::classifyFromTypeAndText('Controle de Documentos Pendentes'));
    }

    #[Test]
    public function classifies_atualizacao_cadastral_as_Informacional(): void
    {
        $this->assertSame('Informacional', ObligationCategoryClassifier::classifyFromTypeAndText('Atualização Cadastral'));
    }

    #[Test]
    public function uses_title_when_type_is_null(): void
    {
        $this->assertSame('Covenants', ObligationCategoryClassifier::classifyFromTypeAndText(
            null,
            'Verificar cumprimento de covenant financeiro',
        ));
    }

    #[Test]
    public function uses_source_excerpt_as_fallback(): void
    {
        $this->assertSame('Fundos', ObligationCategoryClassifier::classifyFromTypeAndText(
            null, null, null,
            'O Fundo de Reserva deverá ser mantido conforme cláusula 10.',
        ));
    }

    // ── categoryColor ─────────────────────────────────────────────────────────

    #[Test]
    public function categoryColor_returns_string_for_all_categories(): void
    {
        foreach (ObligationCategoryClassifier::CATEGORIES as $cat) {
            $color = ObligationCategoryClassifier::categoryColor($cat);
            $this->assertIsString($color, "categoryColor('{$cat}') should return a string");
            $this->assertNotEmpty($color);
        }
    }

    #[Test]
    public function categoryColor_returns_danger_for_VencimentoAntecipado(): void
    {
        $this->assertSame('danger', ObligationCategoryClassifier::categoryColor('Vencimento Antecipado'));
    }

    #[Test]
    public function categoryColor_returns_gray_for_unknown(): void
    {
        $this->assertSame('gray', ObligationCategoryClassifier::categoryColor('Desconhecido'));
        $this->assertSame('gray', ObligationCategoryClassifier::categoryColor('Outro'));
    }
}
