<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermDocumentChunk extends Model
{
    protected $fillable = [
        'term_document_id', 'page_number', 'section_title',
        'clause_reference', 'content', 'sort_order',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'sort_order'  => 'integer',
    ];

    public function termDocument(): BelongsTo
    {
        return $this->belongsTo(TermDocument::class);
    }

    public function extractedObligations(): HasMany
    {
        return $this->hasMany(ExtractedObligation::class);
    }
}
