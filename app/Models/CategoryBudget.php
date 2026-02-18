<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'amount',
        'effective_at',
        'budget_month',
    ];

    protected $casts = [
        'budget_month' => 'date',
        'effective_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
