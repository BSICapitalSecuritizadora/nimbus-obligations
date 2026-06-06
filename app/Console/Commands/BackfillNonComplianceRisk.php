<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Services\NonComplianceRiskService;
use Illuminate\Console\Command;

class BackfillNonComplianceRisk extends Command
{
    protected $signature   = 'obligations:backfill-risk {--force : Overwrite values that are already set}';
    protected $description = 'Backfill non_compliance_risk and non_compliance_consequence for obligations';

    public function handle(NonComplianceRiskService $service): int
    {
        $force = (bool) $this->option('force');

        if ($force) {
            $this->warn('--force active: existing risk values will be overwritten.');
        }

        $query = $force
            ? Obligation::query()
            : Obligation::whereNull('non_compliance_risk');

        $updated = 0;

        $query->chunkById(200, function ($rows) use ($service, $force, &$updated) {
            foreach ($rows as $row) {
                $data = [];

                if ($force || $row->non_compliance_risk === null) {
                    $data['non_compliance_risk'] = $service->suggestRisk($row);
                }

                if ($force || empty($row->non_compliance_consequence)) {
                    $data['non_compliance_consequence'] = $service->suggestConsequence($row);
                }

                if (! empty($data)) {
                    $row->updateQuietly($data);
                    $updated++;
                }
            }
        });

        $this->info("Updated {$updated} obligation(s).");

        return self::SUCCESS;
    }
}
