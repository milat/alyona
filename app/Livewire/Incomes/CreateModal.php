<?php

namespace App\Livewire\Incomes;

use App\Models\Income;
use Livewire\Component;

class CreateModal extends Component
{
    public ?string $description = null;
    public ?string $amount = null;
    public string $received_at = '';

    public function mount(): void
    {
        $this->received_at = now()->toDateString();
    }

    public function save(): void
    {
        $this->amount = $this->normalizeCurrencyValue($this->amount);

        $data = $this->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'received_at' => ['required', 'date'],
        ]);

        $user = auth()->user();

        if (! $user || $user->household_id === null) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        Income::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'received_at' => $data['received_at'],
        ]);

        $this->reset(['description', 'amount']);
        $this->received_at = now()->toDateString();

        session()->flash('success', 'Entrada cadastrada com sucesso.');

        $this->dispatch('income-saved');
    }

    private function normalizeCurrencyValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (! $digits) {
            return null;
        }

        return number_format(((int) $digits) / 100, 2, '.', '');
    }

    public function render()
    {
        return view('livewire.incomes.create-modal');
    }
}
