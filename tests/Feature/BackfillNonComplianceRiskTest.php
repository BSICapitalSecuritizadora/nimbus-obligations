<?php

namespace Tests\Feature;

use App\Models\Obligation;
use App\Models\Operation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillNonComplianceRiskTest extends TestCase
{
    use RefreshDatabase;

    // ── helpers ───────────────────────────────────────────────────────────────

    private function operation(): Operation
    {
        return Operation::create(['name' => 'Op Test', 'type' => 'CRI', 'status' => 'active']);
    }

    private function obligation(Operation $op, array $extra = []): Obligation
    {
        return Obligation::create(array_merge([
            'operation_id'    => $op->id,
            'title'           => 'Obrigação Teste',
            'obligation_type' => 'Relatório Periódico',
            'description'     => 'Descrição',
            'priority'        => 'medium',
            'status'          => 'em_dia',
        ], $extra));
    }

    // ── tests ─────────────────────────────────────────────────────────────────

    #[Test]
    public function backfill_sets_risk_for_obligations_with_null_risk(): void
    {
        $op = $this->operation();
        $ob = $this->obligation($op, ['priority' => 'critical', 'obligation_category' => 'Covenants']);

        $this->assertNull($ob->non_compliance_risk);

        $this->artisan('obligations:backfill-risk')->assertSuccessful();

        $this->assertSame('critico', $ob->fresh()->non_compliance_risk);
    }

    #[Test]
    public function backfill_does_not_overwrite_existing_risk(): void
    {
        $op = $this->operation();
        $ob = $this->obligation($op, [
            'priority'            => 'critical',
            'non_compliance_risk' => 'baixo',
        ]);

        $this->artisan('obligations:backfill-risk')->assertSuccessful();

        // 'baixo' should be preserved even though priority=critical suggests 'critico'
        $this->assertSame('baixo', $ob->fresh()->non_compliance_risk);
    }

    #[Test]
    public function backfill_force_overwrites_existing_risk(): void
    {
        $op = $this->operation();
        $ob = $this->obligation($op, [
            'priority'            => 'critical',
            'non_compliance_risk' => 'baixo',
        ]);

        $this->artisan('obligations:backfill-risk', ['--force' => true])->assertSuccessful();

        $this->assertSame('critico', $ob->fresh()->non_compliance_risk);
    }

    #[Test]
    public function backfill_sets_consequence_for_obligations_with_empty_consequence(): void
    {
        $op = $this->operation();
        $ob = $this->obligation($op, [
            'obligation_category'       => 'Covenants',
            'non_compliance_consequence' => null,
        ]);

        $this->artisan('obligations:backfill-risk')->assertSuccessful();

        $fresh = $ob->fresh();
        $this->assertNotNull($fresh->non_compliance_consequence);
        $this->assertStringContainsString('covenant', $fresh->non_compliance_consequence);
    }

    #[Test]
    public function backfill_does_not_overwrite_existing_consequence(): void
    {
        $op  = $this->operation();
        $custom = 'Consequência personalizada definida pelo analista.';
        $ob  = $this->obligation($op, [
            'non_compliance_risk'        => 'medio',
            'non_compliance_consequence' => $custom,
        ]);

        $this->artisan('obligations:backfill-risk')->assertSuccessful();

        $this->assertSame($custom, $ob->fresh()->non_compliance_consequence);
    }

    #[Test]
    public function backfill_high_priority_covenants_returns_alto(): void
    {
        $op = $this->operation();
        $ob = $this->obligation($op, [
            'priority'            => 'high',
            'obligation_category' => 'Covenants',
        ]);

        $this->artisan('obligations:backfill-risk')->assertSuccessful();

        $this->assertSame('alto', $ob->fresh()->non_compliance_risk);
    }

    #[Test]
    public function backfill_vencimento_antecipado_category_returns_critico(): void
    {
        $op = $this->operation();
        $ob = $this->obligation($op, [
            'priority'            => 'low',
            'obligation_category' => 'Vencimento Antecipado',
        ]);

        $this->artisan('obligations:backfill-risk')->assertSuccessful();

        $this->assertSame('critico', $ob->fresh()->non_compliance_risk);
    }

    #[Test]
    public function approving_obligation_sets_non_compliance_risk_via_service(): void
    {
        $op          = $this->operation();
        $riskService = app(\App\Services\NonComplianceRiskService::class);

        $tempOb = new Obligation([
            'priority'            => 'high',
            'obligation_category' => 'Covenants',
        ]);

        $risk = $riskService->suggestRisk($tempOb);

        $ob = $this->obligation($op, [
            'priority'            => 'high',
            'obligation_category' => 'Covenants',
            'non_compliance_risk' => $risk,
        ]);

        $this->assertSame('alto', $ob->non_compliance_risk);
    }
}
