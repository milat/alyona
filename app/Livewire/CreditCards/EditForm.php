<?php

namespace App\Livewire\CreditCards;

use App\Models\CreditCard;
use Livewire\Component;

class EditForm extends Component
{
    public int $creditCardId;
    public string $title = '';
    public ?string $closing_day = null;
    public ?string $limit = null;
    public ?string $observation = null;
    public bool $is_active = true;

    public function mount(int $creditCardId): void
    {
        $creditCard = $this->getCreditCard($creditCardId);

        $this->creditCardId = $creditCard->id;
        $this->title = $creditCard->title;
        $this->closing_day = (string) $creditCard->closing_day;
        $this->limit = $creditCard->limit !== null ? number_format((float) $creditCard->limit, 2, '.', '') : null;
        $this->observation = $creditCard->observation;
        $this->is_active = (bool) $creditCard->is_active;
    }

    public function save(): void
    {
        $this->limit = $this->normalizeCurrencyValue($this->limit);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'closing_day' => ['required', 'integer', 'min:1', 'max:31'],
            'limit' => ['nullable', 'numeric', 'min:0'],
            'observation' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $creditCard = $this->getCreditCard($this->creditCardId);

        $creditCard->update([
            'title' => $data['title'],
            'closing_day' => $data['closing_day'],
            'limit' => $data['limit'],
            'observation' => $data['observation'],
            'is_active' => $data['is_active'],
        ]);

        session()->flash('success', 'Cartão atualizado com sucesso.');

        $this->redirect(route('credit-cards.index'), navigate: true);
    }

    private function getCreditCard(int $creditCardId): CreditCard
    {
        $user = auth()->user();

        $creditCard = CreditCard::query()
            ->where('id', $creditCardId)
            ->firstOrFail();

        if (! $user || $user->household_id === null || $creditCard->household_id !== $user->household_id) {
            abort(404);
        }

        return $creditCard;
    }

    public function render()
    {
        return view('livewire.credit-cards.edit-form');
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
}
