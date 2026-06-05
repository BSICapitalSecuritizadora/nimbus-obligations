<?php

namespace App\Contracts;

use App\Models\TermDocument;

interface ObligationExtractorInterface
{
    /**
     * Analyse the extracted text of a TermDocument and return an array of
     * obligation proposals. Each proposal is an associative array matching
     * the ExtractedObligation fillable fields (minus the FK columns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function extract(TermDocument $document): array;
}
