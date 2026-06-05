<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'series', 'if_code', 'issuer', 'debtor',
        'assignor', 'fiduciary_agent', 'issue_date', 'maturity_date',
        'status', 'notes',
    ];

    protected $casts = [
        'issue_date'    => 'date',
        'maturity_date' => 'date',
    ];

    // ── Labels ───────────────────────────────────────────────────────────────

    public static function typeOptions(): array
    {
        return [
            'CRI'            => 'CRI',
            'CRA'            => 'CRA',
            'Debenture'      => 'Debêntures',
            'Nota Comercial' => 'Nota Comercial',
            'CCB'            => 'CCB',
            'Other'          => 'Outro',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Ativa',
            'draft'  => 'Rascunho',
            'closed' => 'Encerrada',
        ];
    }

    public function statusLabel(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        return static::typeOptions()[$this->type] ?? $this->type;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function termDocuments(): HasMany
    {
        return $this->hasMany(TermDocument::class);
    }

    public function extractedObligations(): HasMany
    {
        return $this->hasMany(ExtractedObligation::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }
}
