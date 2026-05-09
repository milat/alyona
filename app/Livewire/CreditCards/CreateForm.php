<?php

namespace App\Livewire\CreditCards;

use App\Models\CreditCard;
use Livewire\Component;

class CreateForm extends Component
{
    public string $title = '';
    public ?string $closing_day = null;
    public ?string $limit = null;
    public ?string $observation = null;
    public bool $is_active = true;

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

        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        CreditCard::create([
            'household_id' => $user->household_id,
            'title' => $data['title'],
            'closing_day' => $data['closing_day'],
            'limit' => $data['limit'],
            'observation' => $data['observation'],
            'is_active' => $data['is_active'],
        ]);

        session()->flash('success', 'Cartão criado com sucesso.');

        $this->redirect(route('credit-cards.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.credit-cards.create-form');
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
