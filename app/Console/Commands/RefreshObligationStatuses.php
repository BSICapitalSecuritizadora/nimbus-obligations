<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Services\ObligationStatusService;
use Illuminate\Console\Command;

class RefreshObligationStatuses extends Command
{
    protected $signature   = 'obligations:refresh-statuses {--dry-run : Preview changes without writing}';
    protected $description = 'Recalculate em_dia / a_vencer / vencida based on due_date (manual statuses are preserved)';

    public function handle(ObligationStatusService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no records will be updated.');
        }

        $updated = 0;

        Obligation::whereIn('status', ObligationStatusService::AUTO_STATUSES)
            ->chunkById(200, function ($rows) use ($service, $dryRun, &$updated) {
                foreach ($rows as $row) {
                    $new = $service->calculateFromDueDate($row->due_date);
                    if ($new !== $row->status) {
                        if (! $dryRun) {
                            $row->updateQuietly(['status' => $new]);
                        }
                        $updated++;
                    }
                }
            });

        $this->info($dryRun
            ? "Would update {$updated} obligation(s)."
            : "Updated {$updated} obligation(s)."
        );

        return self::SUCCESS;
    }
}
