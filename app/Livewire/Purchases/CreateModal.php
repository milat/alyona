<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchaseCategoryAllocation;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class CreateModal extends Component
{
    public string $title = '';
    public ?string $description = null;
    public ?int $category_id = null;
    public ?int $payment_method_id = null;
    public ?int $credit_card_id = null;
    public ?string $payment_option = null;
    public ?string $amount = null;
    public ?string $installments = null;
    public string $purchased_at = '';
    public bool $confirming = false;
    public bool $calculatorOpen = false;
    public string $calculatorExpression = '';
    public ?int $subcategoryCalculatorIndex = null;
    public string $subcategoryCalculatorExpression = '';
    public array $subcategories = [];

    public function mount(): void
    {
        $this->purchased_at = now()->toDateString();
    }

    public function addSubcategory(): void
    {
        $this->subcategories[] = ['category_id' => null, 'amount' => null];
    }

    public function removeSubcategory(int $index): void
    {
        unset($this->subcategories[$index]);
        $this->subcategories = array_values($this->subcategories);
    }

    public function openConfirm(): void
    {
        $this->amount = $this->normalizeCurrencyValue($this->amount);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer'],
            'payment_option' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:99'],
            'purchased_at' => ['required', 'date'],
            'subcategories' => ['array'],
            'subcategories.*.category_id' => ['nullable', 'integer'],
            'subcategories.*.amount' => ['nullable'],
        ]);

        if (! $this->validateSubcategories((float) $this->amount)) {
            return;
        }

        if (! $this->resolvePaymentSelection()) {
            $this->addError('payment_option', 'Selecione um meio de pagamento válido.');
            return;
        }

        $this->confirming = true;
    }

    public function backToEdit(): void
    {
        $this->confirming = false;
    }

    public function toggleCalculator(): void
    {
        $this->calculatorOpen = ! $this->calculatorOpen;
    }

    public function toggleSubcategoryCalculator(int $index): void
    {
        if ($this->subcategoryCalculatorIndex === $index) {
            $this->subcategoryCalculatorIndex = null;
            $this->subcategoryCalculatorExpression = '';
            return;
        }

        $this->subcategoryCalculatorIndex = $index;
        $this->subcategoryCalculatorExpression = '';
    }

    public function appendCalculator(string $token): void
    {
        if (! preg_match('/^[0-9+\-*\/.]$/', $token)) {
            return;
        }

        $this->calculatorExpression .= $token;
    }

    public function clearCalculator(): void
    {
        $this->calculatorExpression = '';
    }

    public function backspaceCalculator(): void
    {
        $this->calculatorExpression = mb_substr($this->calculatorExpression, 0, -1);
    }

    public function applyCalculatorResult(): void
    {
        $result = $this->evaluateCalculatorExpression($this->calculatorExpression);

        if ($result === null) {
            $this->addError('amount', 'Expressão inválida na calculadora.');
            return;
        }

        $result = max(0, $result);
        $this->amount = number_format($result, 2, ',', '.');
        $this->calculatorOpen = false;
        $this->calculatorExpression = '';
    }

    public function applySubcategoryCalculatorResult(): void
    {
        if ($this->subcategoryCalculatorIndex === null || ! array_key_exists($this->subcategoryCalculatorIndex, $this->subcategories)) {
            return;
        }

        $result = $this->evaluateCalculatorExpression($this->subcategoryCalculatorExpression);

        if ($result === null) {
            $this->addError('subcategories.' . $this->subcategoryCalculatorIndex . '.amount', 'Expressão inválida na calculadora.');
            return;
        }

        $result = max(0, $result);
        $this->subcategories[$this->subcategoryCalculatorIndex]['amount'] = number_format($result, 2, ',', '.');
        $this->subcategoryCalculatorIndex = null;
        $this->subcategoryCalculatorExpression = '';
    }

    public function save(): void
    {
        $this->amount = $this->normalizeCurrencyValue($this->amount);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer'],
            'payment_option' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:99'],
            'purchased_at' => ['required', 'date'],
            'subcategories' => ['array'],
            'subcategories.*.category_id' => ['nullable', 'integer'],
            'subcategories.*.amount' => ['nullable'],
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

        if (! $this->validateSubcategories((float) $data['amount'])) {
            return;
        }

        $paymentSelection = $this->resolvePaymentSelection();

        if (! $paymentSelection) {
            $this->addError('payment_option', 'Selecione um meio de pagamento válido.');
            return;
        }

        $paymentMethod = $paymentSelection['payment_method'];
        $creditCard = $paymentSelection['credit_card'];
        $isCredit = $creditCard !== null;
        $installments = $isCredit ? max(1, (int) ($data['installments'] ?? 1)) : 1;

        $totalAmount = (float) $data['amount'];
        $baseAmount = round($totalAmount / $installments, 2);
        $accumulated = 0.0;
        $baseReferenceDate = $this->resolveBaseReferenceDate($data['purchased_at'], $creditCard);
        $installmentGroupId = $installments > 1 ? (string) Str::uuid() : null;

        for ($i = 1; $i <= $installments; $i++) {
            $amount = $i === $installments ? round($totalAmount - $accumulated, 2) : $baseAmount;
            $accumulated += $amount;

            $titleSuffix = $installments > 1 ? ' ' . $i . '/' . $installments : '';

            $purchase = Purchase::create([
                'household_id' => $user->household_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'credit_card_id' => $creditCard?->id,
                'title' => $data['title'] . $titleSuffix,
                'description' => $data['description'],
                'amount' => $amount,
                'purchased_at' => $this->resolveInstallmentDate($household, $data['purchased_at'], $i),
                'reference_date' => $baseReferenceDate->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                'installment_group_id' => $installmentGroupId,
                'installment_number' => $installments > 1 ? $i : null,
                'installments_count' => $installments > 1 ? $installments : null,
            ]);

            $this->createSubcategoryAllocations($purchase, $installments, $i, $totalAmount);
        }

        $this->reset(['title', 'description', 'category_id', 'payment_method_id', 'credit_card_id', 'payment_option', 'amount', 'installments', 'subcategories', 'subcategoryCalculatorIndex', 'subcategoryCalculatorExpression']);
        $this->purchased_at = now()->toDateString();
        $this->confirming = false;

        session()->flash('success', 'Compra cadastrada com sucesso.');

        $this->dispatch('purchase-saved');
    }

    private function validateSubcategories(float $purchaseAmount): bool
    {
        $categoryIds = [];
        $total = 0.0;

        foreach ($this->subcategories as $index => $subcategory) {
            $categoryId = (int) ($subcategory['category_id'] ?? 0);
            $amount = $this->normalizeCurrencyValue($subcategory['amount'] ?? null);

            if ($categoryId <= 0 && ($subcategory['amount'] ?? null) === null) {
                continue;
            }

            if ($categoryId <= 0) {
                $this->addError("subcategories.$index.category_id", 'Informe a subcategoria.');
                return false;
            }

            if ($categoryId === (int) $this->category_id || in_array($categoryId, $categoryIds, true)) {
                $this->addError("subcategories.$index.category_id", 'A categoria não pode se repetir na mesma compra.');
                return false;
            }

            if ($amount === null || (float) $amount <= 0) {
                $this->addError("subcategories.$index.amount", 'Informe um valor maior que zero.');
                return false;
            }

            $categoryIds[] = $categoryId;
            $total += (float) $amount;
            $this->subcategories[$index]['amount'] = number_format((float) $amount, 2, ',', '.');
        }

        if ($total >= $purchaseAmount) {
            $this->addError('subcategories', 'A soma das subcategorias deve ser menor que o valor total da compra.');
            return false;
        }

        return true;
    }

    private function normalizedSubcategories(): array
    {
        return collect($this->subcategories)
            ->map(function (array $subcategory) {
                $categoryId = (int) ($subcategory['category_id'] ?? 0);
                $amount = $this->normalizeCurrencyValue($subcategory['amount'] ?? null);

                if ($categoryId <= 0 || $amount === null || (float) $amount <= 0) {
                    return null;
                }

                return ['category_id' => $categoryId, 'amount' => (float) $amount];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function createSubcategoryAllocations(Purchase $purchase, int $installments, int $installmentNumber, float $totalAmount): void
    {
        foreach ($this->normalizedSubcategories() as $subcategory) {
            $baseAmount = round($subcategory['amount'] / $installments, 2);
            $previous = $baseAmount * ($installments - 1);
            $amount = $installmentNumber === $installments
                ? round($subcategory['amount'] - $previous, 2)
                : $baseAmount;

            PurchaseCategoryAllocation::create([
                'purchase_id' => $purchase->id,
                'category_id' => $subcategory['category_id'],
                'amount' => $amount,
            ]);
        }
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

    private function evaluateCalculatorExpression(string $expression): ?float
    {
        $expression = str_replace(',', '.', preg_replace('/\s+/', '', $expression));

        if ($expression === '' || ! preg_match('/^[0-9+\-*\/.]+$/', $expression)) {
            return null;
        }

        preg_match_all('/\d+(?:\.\d+)?|[+\-*\/]/', $expression, $matches);
        $tokens = $matches[0] ?? [];

        if ($tokens === [] || implode('', $tokens) !== $expression) {
            return null;
        }

        $expectNumber = true;
        $values = [];
        $ops = [];

        foreach ($tokens as $token) {
            if (preg_match('/^\d+(?:\.\d+)?$/', $token)) {
                if (! $expectNumber) {
                    return null;
                }
                $values[] = (float) $token;
                $expectNumber = false;
                continue;
            }

            if ($expectNumber) {
                return null;
            }

            while ($ops !== [] && $this->calculatorPrecedence(end($ops)) >= $this->calculatorPrecedence($token)) {
                if (! $this->calculatorApplyTopOperation($values, $ops)) {
                    return null;
                }
            }

            $ops[] = $token;
            $expectNumber = true;
        }

        if ($expectNumber) {
            return null;
        }

        while ($ops !== []) {
            if (! $this->calculatorApplyTopOperation($values, $ops)) {
                return null;
            }
        }

        return isset($values[0]) ? round((float) $values[0], 2) : null;
    }

    private function calculatorPrecedence(string $operator): int
    {
        return in_array($operator, ['*', '/'], true) ? 2 : 1;
    }

    private function calculatorApplyTopOperation(array &$values, array &$ops): bool
    {
        $operator = array_pop($ops);
        $right = array_pop($values);
        $left = array_pop($values);

        if ($operator === null || $left === null || $right === null) {
            return false;
        }

        $result = match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right == 0.0 ? null : $left / $right,
            default => null,
        };

        if ($result === null || is_nan($result) || is_infinite($result)) {
            return false;
        }

        $values[] = $result;

        return true;
    }

    /**
     * @return array{payment_method: PaymentMethod, credit_card: CreditCard|null}|null
     */
    private function resolvePaymentSelection(): ?array
    {
        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            return null;
        }

        if ($this->payment_option && str_starts_with($this->payment_option, 'card:')) {
            $creditCardId = (int) str_replace('card:', '', $this->payment_option);
            $creditCard = CreditCard::query()
                ->where('id', $creditCardId)
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->first();

            $paymentMethod = PaymentMethod::query()
                ->whereRaw('LOWER(name) = ?', ['crédito'])
                ->first();

            if (! $creditCard || ! $paymentMethod) {
                return null;
            }

            $this->payment_method_id = $paymentMethod->id;
            $this->credit_card_id = $creditCard->id;

            return [
                'payment_method' => $paymentMethod,
                'credit_card' => $creditCard,
            ];
        }

        if ($this->payment_option && str_starts_with($this->payment_option, 'method:')) {
            $this->payment_method_id = (int) str_replace('method:', '', $this->payment_option);
            $this->credit_card_id = null;
        }

        if (! $this->payment_method_id) {
            return null;
        }

        $paymentMethod = PaymentMethod::query()
            ->where('id', $this->payment_method_id)
            ->first();

        if (! $paymentMethod) {
            return null;
        }

        $isCreditMethod = mb_strtolower(trim($paymentMethod->name)) === 'crédito';

        if ($isCreditMethod && $this->credit_card_id) {
            $creditCard = CreditCard::query()
                ->where('id', $this->credit_card_id)
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->first();

            if (! $creditCard) {
                return null;
            }

            return [
                'payment_method' => $paymentMethod,
                'credit_card' => $creditCard,
            ];
        }

        if ($isCreditMethod) {
            return null;
        }

        $this->credit_card_id = null;

        return [
            'payment_method' => $paymentMethod,
            'credit_card' => null,
        ];
    }

    private function resolveInstallmentDate($household, string $basePurchasedAt, int $installmentNumber): string
    {
        $baseDate = Carbon::parse($basePurchasedAt)->startOfDay();

        return $baseDate->toDateString();
    }

    private function resolveBaseReferenceDate(string $purchasedAt, ?CreditCard $creditCard): Carbon
    {
        $purchaseDate = Carbon::parse($purchasedAt)->startOfDay();
        $referenceDate = $purchaseDate->copy()->startOfMonth();

        if ($creditCard && $purchaseDate->day >= $creditCard->closing_day) {
            return $referenceDate->addMonthNoOverflow();
        }

        return $referenceDate;
    }

    private function referenceDateForBudgetPreview($household): Carbon
    {
        $purchasedAt = $this->purchased_at ?: now()->toDateString();
        $creditCard = null;

        if ($this->payment_option && str_starts_with($this->payment_option, 'card:')) {
            $creditCard = CreditCard::query()
                ->where('id', (int) str_replace('card:', '', $this->payment_option))
                ->where('household_id', $household->id)
                ->where('is_active', true)
                ->first();
        }

        return $this->resolveBaseReferenceDate($purchasedAt, $creditCard);
    }

    private function spentByCategory(int $householdId, string $start, string $end)
    {
        $allocationTotalsByPurchase = DB::table('purchase_category_allocations')
            ->selectRaw('purchase_id, SUM(amount) as total_allocated')
            ->groupBy('purchase_id');

        $primaryTotals = Purchase::query()
            ->leftJoinSub($allocationTotalsByPurchase, 'purchase_allocations', function ($join) {
                $join->on('purchase_allocations.purchase_id', '=', 'purchases.id');
            })
            ->where('purchases.household_id', $householdId)
            ->whereBetween('purchases.reference_date', [$start, $end])
            ->groupBy('purchases.category_id')
            ->selectRaw('purchases.category_id, SUM(purchases.amount - COALESCE(purchase_allocations.total_allocated, 0)) as total')
            ->pluck('total', 'purchases.category_id');

        $allocationTotals = DB::table('purchase_category_allocations')
            ->join('purchases', 'purchases.id', '=', 'purchase_category_allocations.purchase_id')
            ->where('purchases.household_id', $householdId)
            ->whereBetween('purchases.reference_date', [$start, $end])
            ->groupBy('purchase_category_allocations.category_id')
            ->selectRaw('purchase_category_allocations.category_id, SUM(purchase_category_allocations.amount) as total')
            ->pluck('total', 'purchase_category_allocations.category_id');

        $totals = collect();

        foreach ($primaryTotals as $categoryId => $total) {
            $totals[(int) $categoryId] = (float) $total;
        }

        foreach ($allocationTotals as $categoryId => $total) {
            $categoryId = (int) $categoryId;
            $totals[$categoryId] = (float) ($totals[$categoryId] ?? 0) + (float) $total;
        }

        return $totals;
    }

    public function render()
    {
        $user = auth()->user();
        $categories = collect();
        $remainingByCategory = collect();
        $creditCards = collect();
        $paymentMethodUsage = collect();
        $creditCardUsage = collect();
        $paymentOptions = collect();

        if ($user && $user->household_id !== null && $user->household) {
            $household = $user->household;
            $recentUsageStart = now()->copy()->subDays(90)->toDateString();
            $paymentUsageStart = now()->copy()->subDays(30)->toDateString();

            $recentUsageSubquery = Purchase::query()
                ->selectRaw('category_id, COUNT(*) as recent_usage_count')
                ->where('household_id', $user->household_id)
                ->whereDate('purchased_at', '>=', $recentUsageStart)
                ->groupBy('category_id');

            $categories = Category::query()
                ->leftJoinSub($recentUsageSubquery, 'recent_purchase_usage', function ($join) {
                    $join->on('categories.id', '=', 'recent_purchase_usage.category_id');
                })
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->orderByRaw('COALESCE(recent_purchase_usage.recent_usage_count, 0) DESC')
                ->orderBy('description')
                ->select('categories.*')
                ->get();

            $categoryIds = $categories->pluck('id');
            $referenceDate = $this->referenceDateForBudgetPreview($household);
            $period = BudgetPeriod::forHousehold($household, $referenceDate);

            $budgets = CategoryBudget::query()
                ->whereIn('category_id', $categoryIds)
                ->where(function ($query) use ($period) {
                    $query->whereNull('effective_at')
                        ->orWhere('effective_at', '<=', $period['end']->toDateString());
                })
                ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('category_id')
                ->map(fn ($items) => $items->first());

            $spent = $this->spentByCategory($user->household_id, $period['start']->toDateString(), $period['end']->toDateString());

            $remainingByCategory = $categoryIds->mapWithKeys(function ($id) use ($budgets, $spent) {
                $budget = $budgets->get($id)?->amount;
                if ($budget === null) {
                    return [$id => null];
                }
                $spentValue = (float) ($spent[$id] ?? 0);
                return [$id => (float) $budget - $spentValue];
            });

            $creditCards = CreditCard::query()
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->orderBy('title')
                ->get();

            $paymentMethodUsage = Purchase::query()
                ->selectRaw('payment_method_id, COUNT(*) as usage_count')
                ->where('household_id', $user->household_id)
                ->whereDate('purchased_at', '>=', $paymentUsageStart)
                ->whereNotNull('payment_method_id')
                ->groupBy('payment_method_id')
                ->pluck('usage_count', 'payment_method_id');

            $creditCardUsage = Purchase::query()
                ->selectRaw('credit_card_id, COUNT(*) as usage_count')
                ->where('household_id', $user->household_id)
                ->whereDate('purchased_at', '>=', $paymentUsageStart)
                ->whereNotNull('credit_card_id')
                ->groupBy('credit_card_id')
                ->pluck('usage_count', 'credit_card_id');
        }

        $paymentMethods = PaymentMethod::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (PaymentMethod $method) => mb_strtolower(trim($method->name)) !== 'crédito')
            ->sort(function (PaymentMethod $a, PaymentMethod $b) use ($paymentMethodUsage) {
                $usageComparison = ((int) ($paymentMethodUsage[$b->id] ?? 0)) <=> ((int) ($paymentMethodUsage[$a->id] ?? 0));

                return $usageComparison !== 0 ? $usageComparison : strcasecmp($a->name, $b->name);
            })
            ->values();

        $creditCards = $creditCards
            ->sort(function (CreditCard $a, CreditCard $b) use ($creditCardUsage) {
                $usageComparison = ((int) ($creditCardUsage[$b->id] ?? 0)) <=> ((int) ($creditCardUsage[$a->id] ?? 0));

                return $usageComparison !== 0 ? $usageComparison : strcasecmp($a->title, $b->title);
            })
            ->values();

        $paymentOptions = collect($paymentMethods
            ->map(fn (PaymentMethod $method) => [
                'value' => 'method:' . $method->id,
                'label' => $method->name,
                'usage' => (int) ($paymentMethodUsage[$method->id] ?? 0),
            ])
            ->all())
            ->merge($creditCards->map(fn (CreditCard $creditCard) => [
                'value' => 'card:' . $creditCard->id,
                'label' => 'Crédito (' . $creditCard->title . ')',
                'usage' => (int) ($creditCardUsage[$creditCard->id] ?? 0),
            ])->all())
            ->sort(function (array $a, array $b) {
                $usageComparison = $b['usage'] <=> $a['usage'];

                return $usageComparison !== 0 ? $usageComparison : strcasecmp($a['label'], $b['label']);
            })
            ->values();

        $creditMethodId = PaymentMethod::query()
            ->get()
            ->first(fn (PaymentMethod $method) => mb_strtolower(trim($method->name)) === 'crédito')
            ?->id;

        return view('livewire.purchases.create-modal', [
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'creditCards' => $creditCards,
            'paymentOptions' => $paymentOptions,
            'remainingByCategory' => $remainingByCategory,
            'creditMethodId' => $creditMethodId,
        ]);
    }
}
