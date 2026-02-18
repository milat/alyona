<?php

namespace App\Livewire\Incomes;

use App\Models\Income;
use Livewire\Component;

class EditForm extends Component
{
    public int $incomeId;
    public ?string $description = null;
    public ?string $amount = null;
    public string $received_at = '';

    public function mount(int $incomeId): void
    {
        $income = $this->getIncome($incomeId);

        $this->incomeId = $income->id;
        $this->description = $income->description;
        $this->amount = number_format((float) $income->amount, 2, ',', '.');
        $this->received_at = $income->received_at->toDateString();
    }

    public function save(): void
    {
        $this->amount = $this->normalizeCurrencyValue($this->amount);

        $data = $this->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'received_at' => ['required', 'date'],
        ]);

        $income = $this->getIncome($this->incomeId);
        $income->update($data);

        session()->flash('success', 'Entrada atualizada com sucesso.');

        $this->redirect(route('incomes.index'), navigate: true);
    }

    private function getIncome(int $incomeId): Income
    {
        $user = auth()->user();

        return Income::query()
            ->where('id', $incomeId)
            ->where('household_id', $user->household_id)
            ->firstOrFail();
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
        return view('livewire.incomes.edit-form');
    }
}
