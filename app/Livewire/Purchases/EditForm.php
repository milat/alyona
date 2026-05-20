<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\Purchase;
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
        ]);

        $purchase = $this->getPurchase($this->purchaseId);
        $user = auth()->user();

        $category = Category::query()
            ->where('id', $data['category_id'])
            ->where('household_id', $user->household_id)
            ->where('is_active', true)
            ->firstOrFail();

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

        if ($user && $user->household_id !== null) {
            $categories = Category::query()
                ->where('household_id', $user->household_id)
                ->where('is_active', true)
                ->orderBy('description')
                ->get();

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
        }

        return view('livewire.purchases.edit-form', [
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'creditCards' => $creditCards,
        ]);
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
