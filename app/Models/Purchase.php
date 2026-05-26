<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'user_id',
        'category_id',
        'payment_method_id',
        'credit_card_id',
        'title',
        'description',
        'amount',
        'purchased_at',
        'reference_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'purchased_at' => 'date',
        'reference_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Purchase $purchase) {
            if ($purchase->reference_date || ! $purchase->purchased_at) {
                return;
            }

            $purchasedAt = $purchase->purchased_at->copy()->startOfDay();
            $referenceDate = $purchasedAt->copy()->startOfMonth();
            $creditCard = $purchase->credit_card_id ? CreditCard::find($purchase->credit_card_id) : null;

            if ($creditCard && $purchasedAt->day >= $creditCard->closing_day) {
                $referenceDate->addMonthNoOverflow();
            }

            $purchase->reference_date = $referenceDate->toDateString();
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function categoryAllocations(): HasMany
    {
        return $this->hasMany(PurchaseCategoryAllocation::class);
    }

    public function primaryCategoryAmount(): float
    {
        $allocated = $this->relationLoaded('categoryAllocations')
            ? $this->categoryAllocations->sum(fn (PurchaseCategoryAllocation $allocation) => (float) $allocation->amount)
            : $this->categoryAllocations()->sum('amount');

        return max(0, (float) $this->amount - (float) $allocated);
    }
}
