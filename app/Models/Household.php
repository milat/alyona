<?php

namespace App\Models;

use App\Support\BudgetPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'budget_period_type',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function budgetPeriodOverrides(): HasMany
    {
        return $this->hasMany(HouseholdBudgetPeriodOverride::class);
    }

    public static function budgetPeriodOptions(): array
    {
        return [
            BudgetPeriod::CALENDAR_MONTH,
            BudgetPeriod::FIFTH_BUSINESS_DAY,
        ];
    }
}
