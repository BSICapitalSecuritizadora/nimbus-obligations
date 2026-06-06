<?php

namespace App\Jobs;

use App\Models\TermDocument;

class GenerateObligationsJob extends GenerateTermDocumentObligationsJob
{
    public function __construct(
        TermDocument|int $document,
    )
    {
        parent::__construct($document instanceof TermDocument ? (int) $document->getKey() : (int) $document);
    }
}
