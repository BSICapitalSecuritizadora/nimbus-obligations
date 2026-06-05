<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExtractedObligation extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_id', 'term_document_id', 'term_document_chunk_id',
        'title', 'obligation_type', 'description', 'responsible_party',
        'responsible_area', 'recurrence', 'due_rule', 'due_date', 'priority',
        'status', 'required_evidence', 'source_clause', 'source_page',
        'source_excerpt', 'confidence_score', 'review_notes',
        'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'due_date'         => 'date',
        'reviewed_at'      => 'datetime',
        'confidence_score' => 'float',
        'source_page'      => 'integer',
    ];

    public static function statusOptions(): array
    {
        return [
            'suggested'    => 'Sugerida',
            'approved'     => 'Aprovada',
            'rejected'     => 'Rejeitada',
            'needs_review' => 'Revisar',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low'      => 'Baixa',
            'medium'   => 'Média',
            'high'     => 'Alta',
            'critical' => 'Crítica',
        ];
    }

    public function statusLabel(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function priorityLabel(): string
    {
        return static::priorityOptions()[$this->priority] ?? $this->priority;
    }

    public function confidencePercent(): ?string
    {
        if ($this->confidence_score === null) {
            return null;
        }

        return round($this->confidence_score * 100).'%';
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function termDocument(): BelongsTo
    {
        return $this->belongsTo(TermDocument::class);
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(TermDocumentChunk::class, 'term_document_chunk_id');
    }

    public function obligation(): HasOne
    {
        return $this->hasOne(Obligation::class, 'extracted_obligation_id');
    }
}
