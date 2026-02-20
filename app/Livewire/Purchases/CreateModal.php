<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Livewire\Component;

class CreateModal extends Component
{
    public string $title = '';
    public ?string $description = null;
    public ?int $category_id = null;
    public ?int $payment_method_id = null;
    public ?string $amount = null;
    public ?string $installments = null;
    public string $purchased_at = '';
    public bool $confirming = false;

    public function mount(): void
    {
        $this->purchased_at = now()->toDateString();
    }

    public function openConfirm(): void
    {
        $this->autoAssignCategoryFromDescription();
        $this->amount = $this->normalizeCurrencyValue($this->amount);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer'],
            'payment_method_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:99'],
            'purchased_at' => ['required', 'date'],
        ]);

        $this->confirming = true;
    }

    public function backToEdit(): void
    {
        $this->confirming = false;
    }

    public function updatedDescription(?string $value): void
    {
        $description = $this->normalizeDescriptionMatch($value);

        if ($description === '') {
            return;
        }

        $this->assignCategoryByNormalizedText($description);
    }

    public function autoAssignCategoryFromTitle(): void
    {
        $title = $this->normalizeDescriptionMatch($this->title);

        if ($title === '') {
            return;
        }

        $this->assignCategoryByNormalizedText($title);
    }

    private function assignCategoryByNormalizedText(string $normalizedText): void
    {
        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            return;
        }

        $matchedCategory = Category::query()
            ->where('household_id', $user->household_id)
            ->where('is_active', true)
            ->get(['id', 'default_purchase_description'])
            ->first(function (Category $category) use ($normalizedText) {
                return $this->normalizeDescriptionMatch($category->default_purchase_description) === $normalizedText;
            });

        if ($matchedCategory) {
            $this->category_id = $matchedCategory->id;
        }
    }

    public function save(): void
    {
        $this->autoAssignCategoryFromDescription();
        $this->amount = $this->normalizeCurrencyValue($this->amount);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer'],
            'payment_method_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:99'],
            'purchased_at' => ['required', 'date'],
        ]);

        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            $this->redirect(route('home'), navigate: true);
            return;
        }
        $household = $user->household;

        if (! $household) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        $category = Category::where('id', $data['category_id'])
            ->where('household_id', $user->household_id)
            ->where('is_active', true)
            ->firstOrFail();

        $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])->firstOrFail();

        $isCredit = mb_strtolower(trim($paymentMethod->name)) === 'crédito';
        $installments = $isCredit ? max(1, (int) ($data['installments'] ?? 1)) : 1;

        $totalAmount = (float) $data['amount'];
        $baseAmount = round($totalAmount / $installments, 2);
        $accumulated = 0.0;

        for ($i = 1; $i <= $installments; $i++) {
            $amount = $i === $installments ? round($totalAmount - $accumulated, 2) : $baseAmount;
            $accumulated += $amount;

            $titleSuffix = $installments > 1 ? ' ' . $i . '/' . $installments : '';

            Purchase::create([
                'household_id' => $user->household_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'title' => $data['title'] . $titleSuffix,
                'description' => $data['description'],
                'amount' => $amount,
                'purchased_at' => $this->resolveInstallmentDate($household, $data['purchased_at'], $i),
            ]);
        }

        $this->reset(['title', 'description', 'category_id', 'payment_method_id', 'amount', 'installments']);
        $this->purchased_at = now()->toDateString();
        $this->confirming = false;

        session()->flash('success', 'Compra cadastrada com sucesso.');

        $this->dispatch('purchase-saved');
    }

    private function normalizeCurrencyValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\\D+/', '', $value);

        if (! $digits) {
            return null;
        }

        return number_format(((int) $digits) / 100, 2, '.', '');
    }

    private function normalizeDescriptionMatch(?string $value): string
    {
        $normalized = preg_replace('/\\s+/u', ' ', trim((string) ($value ?? '')));

        return mb_strtolower($normalized);
    }

    private function autoAssignCategoryFromDescription(): void
    {
        $this->updatedDescription($this->description);
    }

    private function resolveInstallmentDate($household, string $basePurchasedAt, int $installmentNumber): string
    {
        $baseDate = Carbon::parse($basePurchasedAt)->startOfDay();
        $periodType = $household->budget_period_type ?? BudgetPeriod::CALENDAR_MONTH;

        if ($periodType !== BudgetPeriod::FIFTH_BUSINESS_DAY) {
            return $baseDate->copy()->addMonthsNoOverflow($installmentNumber - 1)->toDateString();
        }

        $basePeriod = BudgetPeriod::forHousehold($household, $baseDate);
        $targetMonth = Carbon::createFromFormat('Y-m', $basePeriod['period_month'])
            ->startOfMonth()
            ->addMonthsNoOverflow($installmentNumber - 1);

        $targetPeriod = BudgetPeriod::forYearMonth($household, (int) $targetMonth->format('Y'), (int) $targetMonth->format('m'));
        $dayInTargetMonth = min($baseDate->day, $targetMonth->copy()->endOfMonth()->day);

        $candidateDate = Carbon::create(
            (int) $targetMonth->format('Y'),
            (int) $targetMonth->format('m'),
            $dayInTargetMonth
        )->startOfDay();

        if ($candidateDate->lt($targetPeriod['start'])) {
            return $targetPeriod['start']->toDateString();
        }

        if ($candidateDate->gt($targetPeriod['end'])) {
            return $targetPeriod['end']->toDateString();
        }

        return $candidateDate->toDateString();
    }

    public function render()
    {
        $user = auth()->user();
        $categories = collect();
        $remainingByCategory = collect();

        if ($user && $user->household_id !== null && $user->household) {
            $household = $user->household;

            $categories = Category::query()
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->orderBy('description')
                ->get();

            $categoryIds = $categories->pluck('id');
            $period = BudgetPeriod::forHousehold($household, now());

            $budgets = CategoryBudget::query()
                ->whereIn('category_id', $categoryIds)
                ->where(function ($query) {
                    $query->whereNull('effective_at')
                        ->orWhere('effective_at', '<=', now()->toDateString());
                })
                ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('category_id')
                ->map(fn ($items) => $items->first());

            $spent = Purchase::query()
                ->selectRaw('category_id, SUM(amount) as total')
                ->where('household_id', $user->household_id)
                ->whereBetween('purchased_at', [$period['start']->toDateString(), $period['end']->toDateString()])
                ->groupBy('category_id')
                ->pluck('total', 'category_id');

            $remainingByCategory = $categoryIds->mapWithKeys(function ($id) use ($budgets, $spent) {
                $budget = $budgets->get($id)?->amount;
                if ($budget === null) {
                    return [$id => null];
                }
                $spentValue = (float) ($spent[$id] ?? 0);
                return [$id => (float) $budget - $spentValue];
            });
        }

        $paymentMethods = PaymentMethod::query()
            ->orderBy('name')
            ->get();

        $creditMethodId = $paymentMethods
            ->first(fn ($method) => mb_strtolower(trim($method->name)) === 'crédito')
            ?->id;

        return view('livewire.purchases.create-modal', [
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'remainingByCategory' => $remainingByCategory,
            'creditMethodId' => $creditMethodId,
        ]);
    }
}
