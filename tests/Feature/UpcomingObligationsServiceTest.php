<?php

namespace Tests\Feature;

use App\Models\Obligation;
use App\Models\Operation;
use App\Services\UpcomingObligationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpcomingObligationsServiceTest extends TestCase
{
    use RefreshDatabase;

    private UpcomingObligationsService $svc;
    private Operation $op;
    private Operation $otherOp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc     = app(UpcomingObligationsService::class);
        $this->op      = Operation::create(['name' => 'Op A', 'type' => 'CRI', 'status' => 'active']);
        $this->otherOp = Operation::create(['name' => 'Op B', 'type' => 'CRI', 'status' => 'active']);
    }

    private function obligation(array $attrs = []): Obligation
    {
        return Obligation::create(array_merge([
            'operation_id'    => $this->op->id,
            'title'           => 'Obrigação Teste',
            'obligation_type' => 'Relatório Periódico',
            'description'     => 'Descrição',
            'priority'        => 'medium',
            'status'          => 'em_dia',
        ], $attrs));
    }

    // ── getOverdue ────────────────────────────────────────────────────────────

    #[Test]
    public function overdue_obligation_appears_in_get_overdue(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::yesterday()]);

        $results = $this->svc->getOverdue();

        $this->assertTrue($results->contains('id', $ob->id));
    }

    #[Test]
    public function future_obligation_does_not_appear_in_get_overdue(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::tomorrow()]);

        $results = $this->svc->getOverdue();

        $this->assertFalse($results->contains('id', $ob->id));
    }

    // ── getDueIn7Days ─────────────────────────────────────────────────────────

    #[Test]
    public function due_today_appears_in_get_due_in_7_days(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()]);

        $results = $this->svc->getDueIn7Days();

        $this->assertTrue($results->contains('id', $ob->id));
    }

    #[Test]
    public function due_in_5_days_appears_in_get_due_in_7_days(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()->addDays(5)]);

        $results = $this->svc->getDueIn7Days();

        $this->assertTrue($results->contains('id', $ob->id));
    }

    #[Test]
    public function due_in_8_days_does_not_appear_in_get_due_in_7_days(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()->addDays(8)]);

        $results = $this->svc->getDueIn7Days();

        $this->assertFalse($results->contains('id', $ob->id));
    }

    // ── getDueIn30Days ────────────────────────────────────────────────────────

    #[Test]
    public function due_in_20_days_appears_in_get_due_in_30_days(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()->addDays(20)]);

        $results = $this->svc->getDueIn30Days();

        $this->assertTrue($results->contains('id', $ob->id));
    }

    #[Test]
    public function due_in_45_days_does_not_appear_in_get_due_in_30_days(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()->addDays(45)]);

        $results = $this->svc->getDueIn30Days();

        $this->assertFalse($results->contains('id', $ob->id));
    }

    #[Test]
    public function due_in_45_days_does_not_appear_in_any_upcoming_list(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()->addDays(45)]);

        $this->assertFalse($this->svc->getOverdue()->contains('id', $ob->id));
        $this->assertFalse($this->svc->getDueIn7Days()->contains('id', $ob->id));
        $this->assertFalse($this->svc->getDueIn30Days()->contains('id', $ob->id));
    }

    // ── getWithoutDueDate ─────────────────────────────────────────────────────

    #[Test]
    public function obligation_without_due_date_appears_in_get_without_due_date(): void
    {
        $ob = $this->obligation(['due_date' => null]);

        $results = $this->svc->getWithoutDueDate();

        $this->assertTrue($results->contains('id', $ob->id));
    }

    #[Test]
    public function obligation_with_due_date_does_not_appear_in_get_without_due_date(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::today()]);

        $results = $this->svc->getWithoutDueDate();

        $this->assertFalse($results->contains('id', $ob->id));
    }

    // ── Excluded statuses ─────────────────────────────────────────────────────

    #[Test]
    public function concluida_obligation_is_excluded_from_all_lists(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::yesterday(), 'status' => 'concluida']);

        $this->assertFalse($this->svc->getOverdue()->contains('id', $ob->id));
        $this->assertFalse($this->svc->getDueIn7Days()->contains('id', $ob->id));
        $this->assertFalse($this->svc->getDueIn30Days()->contains('id', $ob->id));
    }

    #[Test]
    public function waiver_obligation_is_excluded_from_all_lists(): void
    {
        $ob = $this->obligation(['due_date' => Carbon::yesterday(), 'status' => 'waiver']);

        $this->assertFalse($this->svc->getOverdue()->contains('id', $ob->id));
        $this->assertFalse($this->svc->getWithoutDueDate()->contains('id', $ob->id));
    }

    #[Test]
    public function nao_aplicavel_obligation_is_excluded_from_all_lists(): void
    {
        $ob = $this->obligation(['due_date' => null, 'status' => 'nao_aplicavel']);

        $this->assertFalse($this->svc->getOverdue()->contains('id', $ob->id));
        $this->assertFalse($this->svc->getWithoutDueDate()->contains('id', $ob->id));
    }

    // ── operationId scoping ───────────────────────────────────────────────────

    #[Test]
    public function operation_id_scopes_results_to_that_operation(): void
    {
        $ownOb = $this->obligation(['due_date' => Carbon::yesterday(), 'operation_id' => $this->op->id]);
        $otherOb = Obligation::create([
            'operation_id'    => $this->otherOp->id,
            'title'           => 'Outra Operação',
            'obligation_type' => 'Relatório Periódico',
            'description'     => 'Desc',
            'priority'        => 'medium',
            'status'          => 'em_dia',
            'due_date'        => Carbon::yesterday(),
        ]);

        $scoped = $this->svc->getOverdue($this->op->id);

        $this->assertTrue($scoped->contains('id', $ownOb->id));
        $this->assertFalse($scoped->contains('id', $otherOb->id));
    }

    #[Test]
    public function null_operation_id_returns_all_operations(): void
    {
        $ownOb = $this->obligation(['due_date' => Carbon::yesterday()]);
        $otherOb = Obligation::create([
            'operation_id'    => $this->otherOp->id,
            'title'           => 'Outra',
            'obligation_type' => 'Relatório Periódico',
            'description'     => 'Desc',
            'priority'        => 'medium',
            'status'          => 'em_dia',
            'due_date'        => Carbon::yesterday(),
        ]);

        $global = $this->svc->getOverdue(null);

        $this->assertTrue($global->contains('id', $ownOb->id));
        $this->assertTrue($global->contains('id', $otherOb->id));
    }

    // ── getUpcomingSummary ────────────────────────────────────────────────────

    #[Test]
    public function get_upcoming_summary_returns_correct_counts(): void
    {
        $this->obligation(['due_date' => Carbon::yesterday()]);                     // overdue
        $this->obligation(['due_date' => Carbon::today()->addDays(3)]);            // due_in_7
        $this->obligation(['due_date' => Carbon::today()->addDays(3)]);            // due_in_7
        $this->obligation(['due_date' => Carbon::today()->addDays(20)]);           // due_in_30
        $this->obligation(['due_date' => Carbon::today()->addDays(45)]);           // beyond
        $this->obligation(['due_date' => null]);                                    // no_date
        $this->obligation(['due_date' => Carbon::yesterday(), 'status' => 'concluida']); // excluded

        $summary = $this->svc->getUpcomingSummary();

        $this->assertSame(1, $summary['overdue_count']);
        $this->assertSame(2, $summary['due_in_7_count']);
        $this->assertSame(1, $summary['due_in_30_count']);
        $this->assertSame(1, $summary['no_due_date_count']);
    }

    #[Test]
    public function get_upcoming_summary_scoped_to_operation_id(): void
    {
        $this->obligation(['due_date' => Carbon::yesterday()]);
        Obligation::create([
            'operation_id'    => $this->otherOp->id,
            'title'           => 'Outra',
            'obligation_type' => 'Relatório Periódico',
            'description'     => 'Desc',
            'priority'        => 'medium',
            'status'          => 'em_dia',
            'due_date'        => Carbon::yesterday(),
        ]);

        $summary = $this->svc->getUpcomingSummary($this->op->id);

        $this->assertSame(1, $summary['overdue_count']);
    }
}
