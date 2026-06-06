<?php

namespace Tests\Unit;

use App\Models\Obligation;
use App\Services\NonComplianceRiskService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NonComplianceRiskServiceTest extends TestCase
{
    private NonComplianceRiskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NonComplianceRiskService();
    }

    // ── suggestRisk ───────────────────────────────────────────────────────────

    #[Test]
    public function critical_priority_returns_critico(): void
    {
        $ob = $this->fakeObligation('critical', 'Informacional');
        $this->assertSame('critico', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function vencimento_antecipado_category_returns_critico_regardless_of_priority(): void
    {
        foreach (['low', 'medium', 'high'] as $priority) {
            $ob = $this->fakeObligation($priority, 'Vencimento Antecipado');
            $this->assertSame('critico', $this->service->suggestRisk($ob), "Failed for priority={$priority}");
        }
    }

    #[Test]
    public function high_priority_returns_alto(): void
    {
        $ob = $this->fakeObligation('high', 'Informacional');
        $this->assertSame('alto', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function high_priority_covenant_returns_alto(): void
    {
        $ob = $this->fakeObligation('high', 'Covenants');
        $this->assertSame('alto', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function medium_priority_covenants_category_returns_alto(): void
    {
        $ob = $this->fakeObligation('medium', 'Covenants');
        $this->assertSame('alto', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function medium_priority_fundos_category_returns_alto(): void
    {
        $ob = $this->fakeObligation('medium', 'Fundos');
        $this->assertSame('alto', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function medium_priority_garantias_category_returns_alto(): void
    {
        $ob = $this->fakeObligation('medium', 'Garantias');
        $this->assertSame('alto', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function medium_priority_regulatoria_category_returns_alto(): void
    {
        $ob = $this->fakeObligation('medium', 'Regulatória');
        $this->assertSame('alto', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function medium_priority_returns_medio(): void
    {
        $ob = $this->fakeObligation('medium', 'Informacional');
        $this->assertSame('medio', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function low_priority_returns_baixo(): void
    {
        $ob = $this->fakeObligation('low', 'Informacional');
        $this->assertSame('baixo', $this->service->suggestRisk($ob));
    }

    #[Test]
    public function null_category_low_priority_returns_baixo(): void
    {
        $ob = $this->fakeObligation('low', null);
        $this->assertSame('baixo', $this->service->suggestRisk($ob));
    }

    // ── suggestConsequence ────────────────────────────────────────────────────

    #[Test]
    public function suggest_consequence_for_covenants_mentions_covenant(): void
    {
        $ob = $this->fakeObligation('medium', 'Covenants');
        $this->assertStringContainsString('covenant', $this->service->suggestConsequence($ob));
    }

    #[Test]
    public function suggest_consequence_for_vencimento_antecipado_mentions_vencimento(): void
    {
        $ob = $this->fakeObligation('low', 'Vencimento Antecipado');
        $this->assertStringContainsString('vencimento antecipado', $this->service->suggestConsequence($ob));
    }

    #[Test]
    public function suggest_consequence_for_null_category_returns_fallback(): void
    {
        $ob = $this->fakeObligation('low', null);
        $consequence = $this->service->suggestConsequence($ob);
        $this->assertNotEmpty($consequence);
        $this->assertStringContainsString('pendência', $consequence);
    }

    // ── static helpers ────────────────────────────────────────────────────────

    #[Test]
    public function risk_color_critico_is_danger(): void
    {
        $this->assertSame('danger', NonComplianceRiskService::getRiskColor('critico'));
    }

    #[Test]
    public function risk_color_alto_is_warning(): void
    {
        $this->assertSame('warning', NonComplianceRiskService::getRiskColor('alto'));
    }

    #[Test]
    public function risk_color_medio_is_info(): void
    {
        $this->assertSame('info', NonComplianceRiskService::getRiskColor('medio'));
    }

    #[Test]
    public function risk_color_baixo_is_success(): void
    {
        $this->assertSame('success', NonComplianceRiskService::getRiskColor('baixo'));
    }

    #[Test]
    public function is_valid_accepts_known_values(): void
    {
        foreach (['baixo', 'medio', 'alto', 'critico'] as $v) {
            $this->assertTrue(NonComplianceRiskService::isValid($v));
        }
    }

    #[Test]
    public function is_valid_rejects_unknown_and_null(): void
    {
        $this->assertFalse(NonComplianceRiskService::isValid(null));
        $this->assertFalse(NonComplianceRiskService::isValid('high'));
        $this->assertFalse(NonComplianceRiskService::isValid(''));
    }

    // ── helper ────────────────────────────────────────────────────────────────

    private function fakeObligation(?string $priority, ?string $category): Obligation
    {
        $ob = new Obligation();
        $ob->priority             = $priority;
        $ob->obligation_category  = $category;
        return $ob;
    }
}
