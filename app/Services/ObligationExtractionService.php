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
        // Remove previous AI-generated suggestions that have not been approved or rejected.
        // This covers both 'suggested' (normal) and 'needs_review' (set by older extractor
        // versions before Phase C).  Approved and rejected obligations are preserved.
        ExtractedObligation::where('term_document_id', $document->id)
            ->whereIn('status', ['suggested', 'needs_review'])
            ->delete();

        $proposals = $this->extractor->extract($document);
        $count     = 0;

        foreach ($proposals as $proposal) {
            ExtractedObligation::create(array_merge($proposal, [
                'operation_id'     => $document->operation_id,
                'term_document_id' => $document->id,
                'status'           => 'suggested',
            ]));

            $count++;
        }

        // Persist extraction metadata on the document.
        // Merge provider-specific stats (e.g. Gemini chunk/skip details) when available.
        $providerStats = method_exists($this->extractor, 'getExtractionStats')
            ? $this->extractor->getExtractionStats()
            : [];

        $metadata = array_merge($providerStats, [
            'suggestions_generated' => $count,
            'extracted_at'          => now()->toIso8601String(),
        ]);

        $lastError = $this->extractor->getLastError();
        if ($lastError !== null) {
            $metadata['last_error'] = $lastError;
        }

        $document->update([
            'extraction_provider' => $this->extractor->getProviderName(),
            'extraction_model'    => $this->extractor->getModelName(),
            'extraction_metadata' => $metadata,
        ]);

        return $count;
    }
}
