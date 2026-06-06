<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_id', 'original_filename', 'stored_path', 'mime_type',
        'file_size', 'file_hash', 'processing_status', 'extracted_text',
        'extraction_error', 'uploaded_by', 'processed_at',
        'extraction_provider', 'extraction_model', 'extraction_metadata',
    ];

    protected $casts = [
        'processed_at'        => 'datetime',
        'file_size'           => 'integer',
        'extraction_metadata' => 'array',
    ];

    public static function processingStatusOptions(): array
    {
        return [
            'pending'    => 'Pendente',
            'processing' => 'Processando',
            'processed'  => 'Processado',
            'failed'     => 'Falhou',
        ];
    }

    public static function obligationGenerationStatusOptions(): array
    {
        return [
            'queued'     => 'Na fila',
            'processing' => 'Processando',
            'completed'  => 'Concluído',
            'failed'     => 'Falhou',
        ];
    }

    public function processingStatusLabel(): string
    {
        return static::processingStatusOptions()[$this->processing_status] ?? $this->processing_status;
    }

    public function getObligationGenerationStatusAttribute(): ?string
    {
        $status = $this->extraction_metadata['generation_status'] ?? null;

        return match ($status) {
            'generating' => 'processing',
            'done'       => 'completed',
            default      => $status,
        };
    }

    public function getObligationSuggestionsCreatedAttribute(): ?int
    {
        return $this->extraction_metadata['suggestions_generated']
            ?? $this->extraction_metadata['obligations_created']
            ?? null;
    }

    public function getObligationChunksProcessedAttribute(): ?int
    {
        return $this->extraction_metadata['chunks_processed'] ?? null;
    }

    public function getObligationSkippedItemsAttribute(): ?int
    {
        return $this->extraction_metadata['obligations_skipped'] ?? null;
    }

    public function getObligationExtractionErrorAttribute(): ?string
    {
        if ($this->obligation_generation_status !== 'failed') {
            return null;
        }

        return $this->extraction_metadata['last_error'] ?? $this->extraction_error;
    }

    public function isProcessed(): bool
    {
        return $this->processing_status === 'processed';
    }

    public function fileSizeHuman(): string
    {
        if (! $this->file_size) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->file_size;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(TermDocumentChunk::class)->orderBy('sort_order');
    }

    public function extractedObligations(): HasMany
    {
        return $this->hasMany(ExtractedObligation::class);
    }
}
