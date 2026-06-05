<?php

namespace App\Jobs;

use App\Models\TermDocument;
use App\Services\TermDocumentTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTermDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public TermDocument $document,
    ) {}

    public function handle(TermDocumentTextExtractor $extractor): void
    {
        $extractor->extract($this->document);
    }
}
