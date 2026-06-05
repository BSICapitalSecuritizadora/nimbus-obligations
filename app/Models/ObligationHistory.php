<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligationHistory extends Model
{
    protected $fillable = [
        'obligation_id', 'user_id', 'action',
        'old_value', 'new_value', 'notes',
    ];

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
