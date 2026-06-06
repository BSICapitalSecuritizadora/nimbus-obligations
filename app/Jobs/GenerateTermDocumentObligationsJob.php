<?php

namespace App\Jobs;

use App\Models\TermDocument;
use App\Services\ObligationExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateTermDocumentObligationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public readonly int $termDocumentId,
    ) {}

    public function handle(ObligationExtractionService $service): void
    {
        set_time_limit(0);

        $document = TermDocument::query()->findOrFail($this->termDocumentId);

        $this->markStatus($document, 'processing', [
            'started_at'  => now()->toIso8601String(),
            'finished_at' => null,
            'last_error'  => null,
        ]);
        $document->update(['extraction_error' => null]);

        Log::info('[GenerateTermDocumentObligationsJob] Iniciando geração de obrigações.', [
            'term_document_id' => $document->id,
            'operation_id'     => $document->operation_id,
        ]);

        $count = $service->extractAndSave($document);

        $document = $document->fresh();
        $metadata = $document->extraction_metadata ?? [];

        if ($this->hasFatalExtractorError($count, $metadata)) {
            $message = $this->safeMessage((string) ($metadata['last_error'] ?? 'Falha na extração de obrigações.'));

            $this->markStatus($document, 'failed', [
                'suggestions_generated' => $count,
                'finished_at'           => now()->toIso8601String(),
                'last_error'            => $message,
            ]);

            $document->update(['extraction_error' => $message]);

            Log::warning('[GenerateTermDocumentObligationsJob] Geração finalizada com falha do extrator.', [
                'term_document_id' => $document->id,
                'error'            => $message,
            ]);

            return;
        }

        $this->markStatus($document, 'completed', [
            'suggestions_generated' => $count,
            'finished_at'           => now()->toIso8601String(),
        ]);
        $document->update(['extraction_error' => null]);

        Log::info('[GenerateTermDocumentObligationsJob] Geração de obrigações concluída.', [
            'term_document_id' => $document->id,
            'suggestions'      => $count,
        ]);
    }

    public function failed(Throwable $e): void
    {
        $document = TermDocument::query()->find($this->termDocumentId);
        $message  = $this->safeErrorMessage($e);

        if ($document !== null) {
            $this->markStatus($document, 'failed', [
                'finished_at' => now()->toIso8601String(),
                'last_error'  => $message,
            ]);

            $document->update([
                'extraction_error' => $message,
            ]);
        }

        Log::error('[GenerateTermDocumentObligationsJob] Falha na geração de obrigações.', [
            'term_document_id' => $this->termDocumentId,
            'error'            => $message,
        ]);
    }

    private function markStatus(TermDocument $document, string $status, array $metadata = []): void
    {
        $currentMetadata = $document->fresh()->extraction_metadata ?? [];

        $document->update([
            'extraction_metadata' => array_merge($currentMetadata, [
                'generation_status' => $status,
            ], $metadata),
        ]);
    }

    private function hasFatalExtractorError(int $count, array $metadata): bool
    {
        if ($count > 0 || empty($metadata['last_error'])) {
            return false;
        }

        if (! array_key_exists('chunks_processed', $metadata)) {
            return true;
        }

        return (int) $metadata['chunks_processed'] === 0
            && (int) ($metadata['obligations_skipped'] ?? 0) > 0;
    }

    private function safeErrorMessage(Throwable $e): string
    {
        return $this->safeMessage($e->getMessage());
    }

    private function safeMessage(string $message): string
    {
        $apiKey  = config('obligations.gemini.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $message = str_replace($apiKey, '[redacted]', $message);
        }

        $message = preg_replace('/([?&]key=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;
        $message = preg_replace('/(GEMINI_API_KEY=)[^\s]+/i', '$1[redacted]', $message) ?? $message;

        return $message;
    }
}
