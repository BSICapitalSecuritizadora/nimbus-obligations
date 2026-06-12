<?php

namespace App\Services;

use App\Models\Obligation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UpcomingObligationsService
{
    private const EXCLUDED = ['concluida', 'waiver', 'nao_aplicavel'];

    private const SELECT = [
        'id', 'operation_id', 'title', 'obligation_category',
        'responsible_area', 'due_date', 'status', 'priority', 'non_compliance_risk',
    ];

    // SQLite-compatible CASE expressions for secondary sort
    private const PRIORITY_SQL = "CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END";
    private const RISK_SQL     = "CASE non_compliance_risk WHEN 'critico' THEN 0 WHEN 'alto' THEN 1 WHEN 'medio' THEN 2 WHEN 'baixo' THEN 3 ELSE 4 END";

    private function base(?int $operationId): Builder
    {
        return Obligation::query()
            ->whereNotIn('status', self::EXCLUDED)
            ->when($operationId !== null, fn ($q) => $q->where('operation_id', $operationId));
    }

    public function getOverdue(?int $operationId = null, int $limit = 5): Collection
    {
        $today = Carbon::now()->startOfDay()->toDateString();

        return $this->base($operationId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->select(self::SELECT)
            ->with('operation:id,name')
            ->orderBy('due_date')
            ->orderByRaw(self::PRIORITY_SQL)
            ->orderByRaw(self::RISK_SQL)
            ->limit($limit)
            ->get();
    }

    public function getDueIn7Days(?int $operationId = null, int $limit = 5): Collection
    {
        $today = Carbon::now()->startOfDay()->toDateString();
        $end   = Carbon::now()->startOfDay()->addDays(7)->toDateString();

        return $this->base($operationId)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $end])
            ->select(self::SELECT)
            ->with('operation:id,name')
            ->orderBy('due_date')
            ->orderByRaw(self::PRIORITY_SQL)
            ->orderByRaw(self::RISK_SQL)
            ->limit($limit)
            ->get();
    }

    public function getDueIn30Days(?int $operationId = null, int $limit = 5): Collection
    {
        $start = Carbon::now()->startOfDay()->addDays(8)->toDateString();
        $end   = Carbon::now()->startOfDay()->addDays(30)->toDateString();

        return $this->base($operationId)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->select(self::SELECT)
            ->with('operation:id,name')
            ->orderBy('due_date')
            ->orderByRaw(self::PRIORITY_SQL)
            ->orderByRaw(self::RISK_SQL)
            ->limit($limit)
            ->get();
    }

    public function getWithoutDueDate(?int $operationId = null, int $limit = 5): Collection
    {
        return $this->base($operationId)
            ->whereNull('due_date')
            ->select(self::SELECT)
            ->with('operation:id,name')
            ->orderByRaw(self::PRIORITY_SQL)
            ->orderByRaw(self::RISK_SQL)
            ->limit($limit)
            ->get();
    }

    public function getUpcomingSummary(?int $operationId = null): array
    {
        $today = Carbon::now()->startOfDay()->toDateString();
        $in7   = Carbon::now()->startOfDay()->addDays(7)->toDateString();
        $in8   = Carbon::now()->startOfDay()->addDays(8)->toDateString();
        $in30  = Carbon::now()->startOfDay()->addDays(30)->toDateString();

        return [
            'overdue_count'     => $this->base($operationId)->whereNotNull('due_date')->where('due_date', '<', $today)->count(),
            'due_in_7_count'    => $this->base($operationId)->whereNotNull('due_date')->whereBetween('due_date', [$today, $in7])->count(),
            'due_in_30_count'   => $this->base($operationId)->whereNotNull('due_date')->whereBetween('due_date', [$in8, $in30])->count(),
            'no_due_date_count' => $this->base($operationId)->whereNull('due_date')->count(),
        ];
    }
}
