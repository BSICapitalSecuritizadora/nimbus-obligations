<?php

namespace App\Console\Commands;

use App\Models\ExtractedObligation;
use App\Models\TermDocument;
use Illuminate\Console\Command;

/**
 * Removes non-approved suggested obligations.
 *
 * Safe to run at any time:
 *  - Never deletes approved or rejected obligations.
 *  - Never deletes TermDocument or Operation records.
 *
 * Usage:
 *   php artisan obligations:clear-suggestions              # all documents
 *   php artisan obligations:clear-suggestions --doc=5     # one document
 */
class ClearSuggestionsCommand extends Command
{
    protected $signature = 'obligations:clear-suggestions
        {--doc= : TermDocument ID to clear (omit for all documents)}
        {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete non-approved suggested obligations (suggested + needs_review)';

    public function handle(): int
    {
        $docId = $this->option('doc');
        $dry   = $this->option('dry-run');

        $query = ExtractedObligation::whereIn('status', ['suggested', 'needs_review']);

        if ($docId) {
            if (! TermDocument::find($docId)) {
                $this->error("TermDocument ID {$docId} not found.");
                return self::FAILURE;
            }
            $query->where('term_document_id', $docId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No suggestions to clear.');
            return self::SUCCESS;
        }

        $label = $docId ? "term document #{$docId}" : 'all documents';

        if ($dry) {
            $this->warn("[dry-run] Would delete {$count} suggestion(s) for {$label}.");
            return self::SUCCESS;
        }

        $query->delete();
        $this->info("Deleted {$count} suggestion(s) for {$label}.");

        return self::SUCCESS;
    }
}
