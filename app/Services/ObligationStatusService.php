<?php

namespace App\Services;

use App\Models\Obligation;
use Illuminate\Support\Carbon;

class ObligationStatusService
{
    // Statuses set intentionally by a user — never auto-overridden
    public const MANUAL_STATUSES = ['concluida', 'waiver', 'nao_aplicavel', 'pendente_evidencia', 'em_analise'];

    // Statuses that can be recalculated automatically from due_date
    public const AUTO_STATUSES = ['em_dia', 'a_vencer', 'vencida'];

    private const LEGACY_MAP = [
        'on_track'     => 'em_dia',
        'due_soon'     => 'a_vencer',
        'overdue'      => 'vencida',
        'completed'    => 'concluida',
        'under_review' => 'em_analise',
    ];

    /**
     * Returns the correct status for an obligation, respecting manually-set values.
     */
    public function calculateStatus(Obligation $obligation): string
    {
        if (in_array($obligation->status, static::MANUAL_STATUSES, true)) {
            return $obligation->status;
        }

        return $this->calculateFromDueDate($obligation->due_date);
    }

    /**
     * Calculates the automatic status based solely on a due date.
     */
    public function calculateFromDueDate(?Carbon $dueDate): string
    {
        if ($dueDate === null) {
            return 'em_dia';
        }

        $today = Carbon::now()->startOfDay();
        $due   = $dueDate->copy()->startOfDay();

        if ($due->lt($today)) {
            return 'vencida';
        }

        $days = (int) config('obligations.due_soon_days', 30);
        if ($due->lte($today->copy()->addDays($days))) {
            return 'a_vencer';
        }

        return 'em_dia';
    }

    /**
     * Recalculates and persists the status if it changed (auto statuses only).
     */
    public function updateStatus(Obligation $obligation): void
    {
        $new = $this->calculateStatus($obligation);
        if ($obligation->status !== $new) {
            $obligation->updateQuietly(['status' => $new]);
        }
    }

    /**
     * Maps a legacy status value to its new equivalent.
     */
    public function mapLegacyStatus(?string $status): string
    {
        return static::LEGACY_MAP[$status] ?? ($status ?? 'em_dia');
    }
}
