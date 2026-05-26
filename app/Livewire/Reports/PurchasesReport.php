<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class PurchasesReport extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public array $selectedCategories = [];
    public array $selectedPayments = [];
    public bool $generated = false;

    public function mount(): void
    {
        $this->dateFrom = now()->copy()->subMonthNoOverflow()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function generate(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
            'selectedCategories' => ['array'],
            'selectedPayments' => ['array'],
        ], [
            'dateFrom.required' => 'Informe a data inicial.',
            'dateTo.required' => 'Informe a data final.',
            'dateTo.after_or_equal' => 'A data final deve ser maior ou igual a data inicial.',
        ]);

        $this->generated = true;
    }

    public function render()
    {
        $user = auth()->user();
        $categories = collect();
        $paymentOptions = collect();
        $purchases = collect();
        $total = 0.0;

        if ($user && $user->household_id !== null) {
            $categories = Category::query()
                ->where('household_id', $user->household_id)
                ->orderByDesc('is_active')
                ->orderBy('description')
                ->get(['id', 'description', 'is_active']);

            $paymentOptions = $this->paymentOptions($user->household_id);

            if ($this->generated) {
                $query = Purchase::query()
                    ->with(['category', 'categoryAllocations.category', 'paymentMethod', 'creditCard', 'user'])
                    ->where('household_id', $user->household_id)
                    ->whereBetween('reference_date', [
                        Carbon::parse($this->dateFrom)->toDateString(),
                        Carbon::parse($this->dateTo)->toDateString(),
                    ]);

                $selectedCategoryIds = array_map('intval', $this->selectedCategories);

                if ($selectedCategoryIds !== []) {
                    $query->where(function ($categoryQuery) use ($selectedCategoryIds) {
                        $categoryQuery->whereIn('category_id', $selectedCategoryIds)
                            ->orWhereHas('categoryAllocations', fn ($allocationQuery) => $allocationQuery->whereIn('category_id', $selectedCategoryIds));
                    });
                }

                $this->applyPaymentFilter($query);

                $purchases = $query
                    ->orderByDesc('purchased_at')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(function (Purchase $purchase) use ($selectedCategoryIds) {
                        $amount = $selectedCategoryIds === []
                            ? (float) $purchase->amount
                            : $this->filteredCategoryAmount($purchase, $selectedCategoryIds);

                        $purchase->setAttribute('report_amount', $amount);

                        return $purchase;
                    });

                $total = (float) $purchases->sum('report_amount');
            }
        }

        return view('livewire.reports.purchases-report', [
            'categories' => $categories,
            'paymentOptions' => $paymentOptions,
            'purchases' => $purchases,
            'total' => $total,
        ]);
    }

    private function filteredCategoryAmount(Purchase $purchase, array $selectedCategoryIds): float
    {
        $amount = 0.0;

        if (in_array((int) $purchase->category_id, $selectedCategoryIds, true)) {
            $amount += (float) $purchase->primaryCategoryAmount();
        }

        foreach ($purchase->categoryAllocations as $allocation) {
            if (in_array((int) $allocation->category_id, $selectedCategoryIds, true)) {
                $amount += (float) $allocation->amount;
            }
        }

        return $amount;
    }

    private function paymentOptions(int $householdId): Collection
    {
        $methods = PaymentMethod::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (PaymentMethod $method) => mb_strtolower(trim($method->name)) !== 'crédito')
            ->map(fn (PaymentMethod $method) => [
                'value' => 'method:' . $method->id,
                'label' => $method->name,
            ]);

        $creditCards = CreditCard::query()
            ->where('household_id', $householdId)
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get()
            ->map(fn (CreditCard $creditCard) => [
                'value' => 'card:' . $creditCard->id,
                'label' => 'Crédito (' . $creditCard->title . ')',
            ]);

        return $methods->merge($creditCards)->values();
    }

    private function applyPaymentFilter($query): void
    {
        if ($this->selectedPayments === []) {
            return;
        }

        $methodIds = [];
        $creditCardIds = [];

        foreach ($this->selectedPayments as $payment) {
            if (str_starts_with((string) $payment, 'method:')) {
                $methodIds[] = (int) str_replace('method:', '', (string) $payment);
            }

            if (str_starts_with((string) $payment, 'card:')) {
                $creditCardIds[] = (int) str_replace('card:', '', (string) $payment);
            }
        }

        $query->where(function ($paymentQuery) use ($methodIds, $creditCardIds) {
            if ($methodIds !== []) {
                $paymentQuery->orWhereIn('payment_method_id', $methodIds);
            }

            if ($creditCardIds !== []) {
                $paymentQuery->orWhereIn('credit_card_id', $creditCardIds);
            }
        });
    }
}
