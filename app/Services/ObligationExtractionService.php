<?php

namespace App\Services;

use App\Contracts\ObligationExtractorInterface;
use App\Models\ExtractedObligation;
use App\Models\TermDocument;

class ObligationExtractionService
{
    public function __construct(
        private ObligationExtractorInterface $extractor,
    ) {}

    /**
     * Run extraction and persist the results as ExtractedObligation records.
     * Returns the number of suggestions created.
     */
    public function extractAndSave(TermDocument $document): int
    {
        // Remove previous suggestions for this document that are still "suggested"
        ExtractedObligation::where('term_document_id', $document->id)
            ->where('status', 'suggested')
            ->delete();

        $proposals = $this->extractor->extract($document);
        $count     = 0;

        foreach ($proposals as $proposal) {
            ExtractedObligation::create(array_merge($proposal, [
                'operation_id'    => $document->operation_id,
                'term_document_id' => $document->id,
                'status'          => 'suggested',
            ]));

            $count++;
        }

        return $count;
    }
}
