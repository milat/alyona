<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchaseCategoryAllocation;
use Carbon\Carbon;
use Livewire\Component;

class EditForm extends Component
{
    public int $purchaseId;
    public string $title = '';
    public ?string $description = null;
    public ?int $category_id = null;
    public ?string $payment_option = null;
    public ?int $payment_method_id = null;
    public ?int $credit_card_id = null;
    public ?string $amount = null;
    public string $purchased_at = '';
    public array $subcategories = [];
    public ?int $subcategoryCalculatorIndex = null;
    public string $subcategoryCalculatorExpression = '';
    public ?string $returnMonth = null;

    public function mount(int $purchaseId, ?string $returnMonth = null): void
    {
        $purchase = $this->getPurchase($purchaseId);

        $this->purchaseId = $purchase->id;
        $this->returnMonth = $returnMonth
            ?: request()->query('mes')
            ?: $purchase->reference_date?->format('Y-m')
            ?: $purchase->purchased_at->format('Y-m');
        $this->title = $purchase->title;
        $this->description = $purchase->description;
        $this->category_id = $purchase->category_id;
        $this->payment_method_id = $purchase->payment_method_id;
        $this->credit_card_id = $purchase->credit_card_id;
        $this->payment_option = $purchase->credit_card_id
            ? 'card:' . $purchase->credit_card_id
            : 'method:' . $purchase->payment_method_id;
        $this->amount = number_format((float) $purchase->amount, 2, ',', '.');
        $this->purchased_at = $purchase->purchased_at->toDateString();
        $this->subcategories = $purchase->categoryAllocations()
            ->get(['category_id', 'amount'])
            ->map(fn (PurchaseCategoryAllocation $allocation) => [
                'category_id' => $allocation->category_id,
                'amount' => number_format((float) $allocation->amount, 2, ',', '.'),
            ])
            ->values()
            ->all();
    }

    public function addSubcategory(): void
    {
        $this->subcategories[] = ['category_id' => null, 'amount' => null];
    }

    public function removeSubcategory(int $index): void
    {
        unset($this->subcategories[$index]);
        $this->subcategories = array_values($this->subcategories);

        if ($this->subcategoryCalculatorIndex === $index) {
            $this->subcategoryCalculatorIndex = null;
            $this->subcategoryCalculatorExpression = '';
        }
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
            'payment_option' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'purchased_at' => ['required', 'date'],
            'subcategories' => ['array'],
            'subcategories.*.category_id' => ['nullable', 'integer'],
            'subcategories.*.amount' => ['nullable'],
        ]);

        $purchase = $this->getPurchase($this->purchaseId);
        $user = auth()->user();

        $category = Category::query()
            ->where('id', $data['category_id'])
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

        $purchase->update([
            'category_id' => $category->id,
            'payment_method_id' => $paymentSelection['payment_method']->id,
            'credit_card_id' => $paymentSelection['credit_card']?->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'purchased_at' => Carbon::parse($data['purchased_at'])->toDateString(),
            'reference_date' => $this->resolveReferenceDate($data['purchased_at'], $paymentSelection['credit_card'])->toDateString(),
        ]);

        $purchase->categoryAllocations()->delete();
        foreach ($this->normalizedSubcategories() as $subcategory) {
            PurchaseCategoryAllocation::create([
                'purchase_id' => $purchase->id,
                'category_id' => $subcategory['category_id'],
                'amount' => $subcategory['amount'],
            ]);
        }

        session()->flash('success', 'Compra atualizada com sucesso.');

        $this->redirect($this->purchasesIndexUrl(), navigate: true);
    }


    private function purchasesIndexUrl(): string
    {
        return route('purchases.index', array_filter([
            'mes' => $this->returnMonth,
        ]));
    }

    private function getPurchase(int $purchaseId): Purchase
    {
        $user = auth()->user();

        $purchase = Purchase::query()
            ->where('id', $purchaseId)
            ->firstOrFail();

        if (! $user || $user->household_id === null || $purchase->household_id !== $user->household_id) {
            abort(404);
        }

        return $purchase;
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

        if (str_starts_with((string) $this->payment_option, 'card:')) {
            $creditCard = CreditCard::query()
                ->where('id', (int) str_replace('card:', '', (string) $this->payment_option))
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->first();

            $paymentMethod = PaymentMethod::query()
                ->whereRaw('LOWER(name) = ?', ['crédito'])
                ->first();

            if (! $creditCard || ! $paymentMethod) {
                return null;
            }

            return [
                'payment_method' => $paymentMethod,
                'credit_card' => $creditCard,
            ];
        }

        if (str_starts_with((string) $this->payment_option, 'method:')) {
            $paymentMethod = PaymentMethod::query()
                ->where('id', (int) str_replace('method:', '', (string) $this->payment_option))
                ->first();

            if (! $paymentMethod || mb_strtolower(trim($paymentMethod->name)) === 'crédito') {
                return null;
            }

            return [
                'payment_method' => $paymentMethod,
                'credit_card' => null,
            ];
        }

        return null;
    }

    public function render()
    {
        $user = auth()->user();
        $categories = collect();
        $paymentMethods = collect();
        $creditCards = collect();
        $paymentMethodUsage = collect();
        $creditCardUsage = collect();
        $paymentOptions = collect();

        if ($user && $user->household_id !== null) {
            $categories = Category::query()
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->orderBy('description')
                ->get();

            $paymentUsageStart = now()->copy()->subDays(30)->toDateString();

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

            $paymentMethods = PaymentMethod::query()
                ->orderBy('name')
                ->get()
                ->filter(fn (PaymentMethod $method) => mb_strtolower(trim($method->name)) !== 'crédito')
                ->values();

            $creditCards = CreditCard::query()
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->orderBy('title')
                ->get();

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
        }

        return view('livewire.purchases.edit-form', [
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'creditCards' => $creditCards,
            'paymentOptions' => $paymentOptions,
        ]);
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

    private function resolveReferenceDate(string $purchasedAt, ?CreditCard $creditCard): Carbon
    {
        $purchaseDate = Carbon::parse($purchasedAt)->startOfDay();
        $referenceDate = $purchaseDate->copy()->startOfMonth();

        if ($creditCard && $purchaseDate->day >= $creditCard->closing_day) {
            return $referenceDate->addMonthNoOverflow();
        }

        return $referenceDate;
    }
}
