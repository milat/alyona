<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdBudgetPeriodOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'period_month',
        'start_date',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
