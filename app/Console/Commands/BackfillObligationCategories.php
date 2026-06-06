<?php

namespace App\Console\Commands;

use App\Models\ExtractedObligation;
use App\Models\Obligation;
use App\Services\ObligationCategoryClassifier;
use Illuminate\Console\Command;

class BackfillObligationCategories extends Command
{
    protected $signature   = 'obligations:backfill-categories {--dry-run : Preview counts without writing}';
    protected $description = 'Backfill obligation_category on existing records using the keyword classifier';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no records will be updated.');
        }

        $this->line('Backfilling ExtractedObligations…');
        $eoUpdated = 0;

        ExtractedObligation::whereNull('obligation_category')->chunkById(200, function ($rows) use ($dryRun, &$eoUpdated) {
            foreach ($rows as $row) {
                $cat = ObligationCategoryClassifier::classifyFromTypeAndText(
                    $row->obligation_type,
                    $row->title,
                    $row->description,
                    $row->source_excerpt,
                );
                if (! $dryRun) {
                    $row->updateQuietly(['obligation_category' => $cat]);
                }
                $eoUpdated++;
            }
        });
        $this->line("  ExtractedObligations: {$eoUpdated} record(s) classified.");

        $this->line('Backfilling Obligations…');
        $obUpdated = 0;

        Obligation::whereNull('obligation_category')->chunkById(200, function ($rows) use ($dryRun, &$obUpdated) {
            foreach ($rows as $row) {
                $cat = ObligationCategoryClassifier::classifyFromTypeAndText(
                    $row->obligation_type,
                    $row->title,
                    $row->description,
                    $row->source_excerpt,
                );
                if (! $dryRun) {
                    $row->updateQuietly(['obligation_category' => $cat]);
                }
                $obUpdated++;
            }
        });
        $this->line("  Obligations: {$obUpdated} record(s) classified.");

        $this->info($dryRun ? 'Dry run complete.' : 'Backfill complete.');

        return self::SUCCESS;
    }
}
