<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'title',
        'closing_day',
        'limit',
        'observation',
        'is_active',
    ];

    protected $casts = [
        'closing_day' => 'integer',
        'limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
