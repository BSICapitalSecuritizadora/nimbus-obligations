<?php

namespace App\Filament\Resources\OperationResource\Pages;

use App\Filament\Resources\ExtractedObligationResource;
use App\Filament\Resources\ObligationResource;
use App\Filament\Resources\OperationResource;
use App\Filament\Resources\TermDocumentResource;
use App\Models\ExtractedObligation;
use App\Models\Obligation;
use App\Models\ObligationHistory;
use App\Models\Operation;
use App\Models\TermDocument;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OperationOverview extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OperationResource::class;
    protected static string $view     = 'filament.resources.operation-resource.pages.operation-overview';

    public array $stats             = [];
    public array $health            = [];
    public array $termDocuments     = [];
    public array $suggestedObs      = [];
    public array $approvedObs       = [];
    public array $dueSoon           = [];
    public array $categoryBreakdown = [];

    // Pre-computed index URLs — used by the Blade view so no class references are needed there
    public string $extractedObligationsIndexUrl = '';
    public string $obligationsIndexUrl          = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadData();
    }

    protected function loadData(): void
    {
        $id  = $this->record->id;
        $now = Carbon::now();

        // ── Counts ────────────────────────────────────────────────────────────
        $approvedCount  = Obligation::where('operation_id', $id)->count();
        $suggestedCount = ExtractedObligation::where('operation_id', $id)
                              ->whereIn('status', ['suggested', 'needs_review'])->count();
        $criticalCount  = Obligation::where('operation_id', $id)
                              ->where('priority', 'critical')
                              ->where('status', '!=', 'completed')->count();
        $overdueCount   = Obligation::where('operation_id', $id)->where('status', 'overdue')->count();
        $dueSoonCount   = Obligation::where('operation_id', $id)->where('status', 'due_soon')->count();
        $completedCount = Obligation::where('operation_id', $id)->where('status', 'completed')->count();
        $docsCount      = TermDocument::where('operation_id', $id)->count();
        $lastAi         = TermDocument::where('operation_id', $id)
                              ->whereNotNull('processed_at')
                              ->latest('processed_at')
                              ->value('processed_at');

        $this->stats = [
            'approved_count'       => $approvedCount,
            'suggested_count'      => $suggestedCount,
            'critical_count'       => $criticalCount,
            'overdue_count'        => $overdueCount,
            'due_soon_count'       => $dueSoonCount,
            'completed_count'      => $completedCount,
            'term_documents_count' => $docsCount,
            'last_ai_processing'   => $lastAi ? Carbon::parse($lastAi)->format('d/m/Y H:i') : '—',
        ];

        // ── Health snapshot ────────────────────────────────────────────────────
        if ($overdueCount > 0 || $criticalCount > 0) {
            $healthStatus = 'critical';
            if ($overdueCount > 0 && $criticalCount > 0) {
                $mainPoint = "{$overdueCount} obrigação(ões) vencida(s) e {$criticalCount} crítica(s) pendente(s).";
            } elseif ($overdueCount > 0) {
                $mainPoint = "{$overdueCount} obrigação(ões) vencida(s). Atenção imediata recomendada.";
            } else {
                $mainPoint = "{$criticalCount} obrigação(ões) crítica(s) pendente(s) de resolução.";
            }
        } elseif ($dueSoonCount > 0 || $suggestedCount > 0) {
            $healthStatus = 'attention';
            if ($dueSoonCount > 0 && $suggestedCount > 0) {
                $mainPoint = "{$dueSoonCount} obrigação(ões) a vencer em breve e {$suggestedCount} sugestão(ões) aguardando revisão.";
            } elseif ($dueSoonCount > 0) {
                $mainPoint = "{$dueSoonCount} obrigação(ões) com prazo se aproximando. Verifique os vencimentos.";
            } else {
                $mainPoint = "{$suggestedCount} sugestão(ões) aguardando revisão e aprovação.";
            }
        } else {
            $healthStatus = 'healthy';
            $mainPoint    = 'Nenhuma obrigação vencida ou crítica identificada. Monitoramento em dia.';
        }

        $recommendedAction = 'Nenhuma ação crítica no momento. Continue monitorando.';
        if ($overdueCount > 0) {
            $recommendedAction = "Regularizar {$overdueCount} obrigação(ões) vencida(s).";
        } elseif ($suggestedCount > 0) {
            $recommendedAction = "Revisar {$suggestedCount} sugestão(ões) pendente(s).";
        } elseif ($criticalCount > 0) {
            $recommendedAction = "Acompanhar {$criticalCount} obrigação(ões) crítica(s).";
        } elseif ($dueSoonCount > 0) {
            $recommendedAction = "Acompanhar {$dueSoonCount} obrigação(ões) a vencer em breve.";
        }

        $this->health = [
            'status'              => $healthStatus,
            'main_point'          => $mainPoint,
            'overdue_count'       => $overdueCount,
            'critical_count'      => $criticalCount,
            'pending_suggestions' => $suggestedCount,
            'last_ai_processing'  => $this->stats['last_ai_processing'],
            'recommended_action'  => $recommendedAction,
        ];

        // ── Term documents (no extracted_text loaded) ─────────────────────────
        $this->termDocuments = TermDocument::where('operation_id', $id)
            ->select(['id', 'original_filename', 'processing_status', 'extraction_provider',
                      'extraction_model', 'extraction_metadata', 'extraction_error', 'processed_at'])
            ->latest()
            ->get()
            ->map(fn ($doc) => array_merge($doc->toArray(), [
                'url_view' => TermDocumentResource::getUrl('view', ['record' => $doc->id]),
            ]))
            ->toArray();

        // ── Suggested obligations (Limit 5) ───────────────────────────────────
        $this->suggestedObs = ExtractedObligation::where('operation_id', $id)
            ->whereIn('status', ['suggested', 'needs_review'])
            ->select(['id', 'title', 'obligation_type', 'obligation_category', 'priority', 'confidence_score', 'source_clause', 'status'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($ob) => array_merge($ob->toArray(), [
                'url_view' => ExtractedObligationResource::getUrl('view', ['record' => $ob->id]),
            ]))
            ->toArray();

        // ── Approved obligations — most urgent first (Limit 5) ────────────────
        $this->approvedObs = Obligation::where('operation_id', $id)
            ->select(['id', 'title', 'obligation_type', 'obligation_category', 'responsible_area', 'due_date', 'status', 'priority'])
            ->orderByRaw("CASE WHEN status = 'overdue' THEN 0 WHEN status = 'due_soon' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn ($ob) => array_merge($ob->toArray(), [
                'url_view' => ObligationResource::getUrl('view', ['record' => $ob->id]),
                'url_edit' => ObligationResource::getUrl('edit', ['record' => $ob->id]),
            ]))
            ->toArray();

        // ── Due soon breakdown ────────────────────────────────────────────────
        $this->dueSoon = [
            'overdue'   => Obligation::where('operation_id', $id)
                              ->where('status', 'overdue')
                              ->whereNotNull('due_date')
                              ->orderBy('due_date')
                              ->select(['id', 'title', 'due_date', 'priority', 'obligation_type'])
                              ->limit(5)->get()->toArray(),
            'within_7'  => Obligation::where('operation_id', $id)
                              ->whereNotNull('due_date')
                              ->whereBetween('due_date', [
                                  $now->toDateString(),
                                  $now->copy()->addDays(7)->toDateString(),
                              ])
                              ->orderBy('due_date')
                              ->select(['id', 'title', 'due_date', 'priority', 'obligation_type'])
                              ->limit(5)->get()->toArray(),
            'within_30' => Obligation::where('operation_id', $id)
                              ->whereNotNull('due_date')
                              ->whereBetween('due_date', [
                                  $now->copy()->addDays(8)->toDateString(),
                                  $now->copy()->addDays(30)->toDateString(),
                              ])
                              ->orderBy('due_date')
                              ->select(['id', 'title', 'due_date', 'priority', 'obligation_type'])
                              ->limit(5)->get()->toArray(),
        ];

        // ── Category breakdown (by obligation_category) ───────────────────────
        $rawCategories = Obligation::where('operation_id', $id)
            ->select('obligation_category', DB::raw('count(*) as total'))
            ->groupBy('obligation_category')
            ->orderBy('total', 'desc')
            ->get();

        $topCategories = $rawCategories->take(8)->map(fn ($row) => [
            'type'  => $row->obligation_category ?: 'Outro',
            'count' => (int) $row->total,
        ])->toArray();

        $othersCount = $rawCategories->skip(8)->sum('total');
        if ($othersCount > 0) {
            $topCategories[] = ['type' => 'Outros', 'count' => (int) $othersCount];
        }

        $this->categoryBreakdown = $topCategories;

        // ── Index URLs — pre-computed so Blade needs no class references ──────
        $this->extractedObligationsIndexUrl = ExtractedObligationResource::getUrl('index');
        $this->obligationsIndexUrl          = ObligationResource::getUrl('index');
    }

    // ── Inline actions — mirror the existing ExtractedObligationResource flow ──

    public function approveSuggestion(int $id): void
    {
        $extracted = ExtractedObligation::find($id);

        if (! $extracted || $extracted->operation_id !== $this->record->id) {
            return;
        }

        $full = $extracted->fresh();

        $obligation = Obligation::create([
            'operation_id'            => $full->operation_id,
            'extracted_obligation_id' => $full->id,
            'title'                   => $full->title,
            'obligation_type'         => $full->obligation_type,
            'obligation_category'     => $full->obligation_category,
            'description'             => $full->description,
            'responsible_party'       => $full->responsible_party,
            'responsible_area'        => $full->responsible_area,
            'recurrence'              => $full->recurrence,
            'due_rule'                => $full->due_rule,
            'due_date'                => $full->due_date,
            'priority'                => $full->priority,
            'status'                  => 'on_track',
            'required_evidence'       => $full->required_evidence,
            'source_clause'           => $full->source_clause,
            'source_page'             => $full->source_page,
            'source_excerpt'          => $full->source_excerpt,
        ]);

        ObligationHistory::create([
            'obligation_id' => $obligation->id,
            'action'        => 'Obrigação criada a partir de sugestão aprovada (Visão da Operação).',
            'new_value'     => 'on_track',
        ]);

        $extracted->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->user()?->name ?? 'Sistema',
            'reviewed_at' => now(),
        ]);

        Notification::make()->title('Obrigação aprovada e criada com sucesso!')->success()->send();
        $this->loadData();
    }

    public function rejectSuggestion(int $id): void
    {
        $extracted = ExtractedObligation::find($id);

        if (! $extracted || $extracted->operation_id !== $this->record->id) {
            return;
        }

        $extracted->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->user()?->name ?? 'Sistema',
            'reviewed_at' => now(),
        ]);

        Notification::make()->title('Sugestão rejeitada.')->warning()->send();
        $this->loadData();
    }

    public function markObligationCompleted(int $id): void
    {
        $obligation = Obligation::find($id);

        if (! $obligation || $obligation->operation_id !== $this->record->id) {
            return;
        }

        $obligation->update(['status' => 'completed']);

        ObligationHistory::create([
            'obligation_id' => $obligation->id,
            'action'        => 'Obrigação marcada como concluída (Visão da Operação).',
            'new_value'     => 'completed',
        ]);

        Notification::make()->title('Obrigação marcada como concluída.')->success()->send();
        $this->loadData();
    }

    // ── Page config ────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Voltar para Operações')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OperationResource::getUrl('index')),

            Actions\Action::make('view_operation')
                ->label('Ver Operação')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => OperationResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Visão da Operação';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Monitoramento consolidado das obrigações, documentos, prazos e riscos vinculados à emissão.';
    }
}

