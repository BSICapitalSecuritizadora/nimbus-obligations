<?php

namespace App\Contracts;

use App\Models\TermDocument;

interface ObligationExtractorInterface
{
    /**
     * Analyse a TermDocument and return an array of obligation proposals.
     * Each proposal is an associative array matching ExtractedObligation
     * fillable fields (minus FK columns).
     *
     * Implementations must NOT throw — capture errors internally and
     * return an empty array, surfacing the error via getLastError().
     *
     * @return array<int, array<string, mixed>>
     */
    public function extract(TermDocument $document): array;

    /** Human-readable provider name, e.g. "mock" or "gemini". */
    public function getProviderName(): string;

    /** Model identifier used for the last extraction, e.g. "gemini-2.5-flash". */
    public function getModelName(): string;

    /** Returns the last error message, or null if the extraction succeeded. */
    public function getLastError(): ?string;
}
