<?php

namespace Tests\Unit;

use App\Models\Obligation;
use App\Services\ObligationStatusService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ObligationStatusServiceTest extends TestCase
{
    private ObligationStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ObligationStatusService();
    }

    // ── calculateFromDueDate ──────────────────────────────────────────────────

    #[Test]
    public function null_due_date_returns_em_dia(): void
    {
        $this->assertSame('em_dia', $this->service->calculateFromDueDate(null));
    }

    #[Test]
    public function past_due_date_returns_vencida(): void
    {
        $this->assertSame('vencida', $this->service->calculateFromDueDate(Carbon::yesterday()));
        $this->assertSame('vencida', $this->service->calculateFromDueDate(Carbon::now()->subDays(30)));
        $this->assertSame('vencida', $this->service->calculateFromDueDate(Carbon::now()->subYear()));
    }

    #[Test]
    public function due_date_today_returns_a_vencer(): void
    {
        $this->assertSame('a_vencer', $this->service->calculateFromDueDate(Carbon::today()));
    }

    #[Test]
    public function due_date_within_30_days_returns_a_vencer(): void
    {
        $this->assertSame('a_vencer', $this->service->calculateFromDueDate(Carbon::now()->addDays(1)));
        $this->assertSame('a_vencer', $this->service->calculateFromDueDate(Carbon::now()->addDays(15)));
        $this->assertSame('a_vencer', $this->service->calculateFromDueDate(Carbon::now()->addDays(30)));
    }

    #[Test]
    public function due_date_beyond_30_days_returns_em_dia(): void
    {
        $this->assertSame('em_dia', $this->service->calculateFromDueDate(Carbon::now()->addDays(31)));
        $this->assertSame('em_dia', $this->service->calculateFromDueDate(Carbon::now()->addYear()));
    }

    // ── calculateStatus with manual statuses ─────────────────────────────────

    #[Test]
    public function concluida_is_not_overridden(): void
    {
        $obligation = $this->fakeObligation('concluida', Carbon::yesterday());
        $this->assertSame('concluida', $this->service->calculateStatus($obligation));
    }

    #[Test]
    public function waiver_is_not_overridden(): void
    {
        $obligation = $this->fakeObligation('waiver', Carbon::yesterday());
        $this->assertSame('waiver', $this->service->calculateStatus($obligation));
    }

    #[Test]
    public function nao_aplicavel_is_not_overridden(): void
    {
        $obligation = $this->fakeObligation('nao_aplicavel', Carbon::yesterday());
        $this->assertSame('nao_aplicavel', $this->service->calculateStatus($obligation));
    }

    #[Test]
    public function pendente_evidencia_is_not_overridden(): void
    {
        $obligation = $this->fakeObligation('pendente_evidencia', Carbon::yesterday());
        $this->assertSame('pendente_evidencia', $this->service->calculateStatus($obligation));
    }

    #[Test]
    public function em_analise_is_not_overridden(): void
    {
        $obligation = $this->fakeObligation('em_analise', Carbon::yesterday());
        $this->assertSame('em_analise', $this->service->calculateStatus($obligation));
    }

    #[Test]
    public function em_dia_with_past_due_date_becomes_vencida(): void
    {
        $obligation = $this->fakeObligation('em_dia', Carbon::yesterday());
        $this->assertSame('vencida', $this->service->calculateStatus($obligation));
    }

    #[Test]
    public function em_dia_with_null_due_date_stays_em_dia(): void
    {
        $obligation = $this->fakeObligation('em_dia', null);
        $this->assertSame('em_dia', $this->service->calculateStatus($obligation));
    }

    // ── mapLegacyStatus ───────────────────────────────────────────────────────

    #[Test]
    public function maps_on_track_to_em_dia(): void
    {
        $this->assertSame('em_dia', $this->service->mapLegacyStatus('on_track'));
    }

    #[Test]
    public function maps_due_soon_to_a_vencer(): void
    {
        $this->assertSame('a_vencer', $this->service->mapLegacyStatus('due_soon'));
    }

    #[Test]
    public function maps_overdue_to_vencida(): void
    {
        $this->assertSame('vencida', $this->service->mapLegacyStatus('overdue'));
    }

    #[Test]
    public function maps_completed_to_concluida(): void
    {
        $this->assertSame('concluida', $this->service->mapLegacyStatus('completed'));
    }

    #[Test]
    public function maps_under_review_to_em_analise(): void
    {
        $this->assertSame('em_analise', $this->service->mapLegacyStatus('under_review'));
    }

    #[Test]
    public function maps_null_to_em_dia(): void
    {
        $this->assertSame('em_dia', $this->service->mapLegacyStatus(null));
    }

    #[Test]
    public function passes_through_new_status_values_unchanged(): void
    {
        foreach (['em_dia', 'a_vencer', 'vencida', 'concluida', 'em_analise', 'waiver', 'nao_aplicavel', 'pendente_evidencia'] as $status) {
            $this->assertSame($status, $this->service->mapLegacyStatus($status));
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function fakeObligation(string $status, ?Carbon $dueDate): Obligation
    {
        $ob           = new Obligation();
        $ob->status   = $status;
        $ob->due_date = $dueDate;
        return $ob;
    }
}
