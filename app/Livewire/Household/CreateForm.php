<?php

namespace App\Livewire\Household;

use App\Models\Category;
use App\Models\Household;
use App\Support\BudgetPeriod;
use Livewire\Component;

class CreateForm extends Component
{
    public string $name = '';
    public string $budget_period_type = BudgetPeriod::CALENDAR_MONTH;

    public function create(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'budget_period_type' => ['required', 'in:' . implode(',', Household::budgetPeriodOptions())],
        ]);

        $user = auth()->user();

        if ($user->household_id !== null) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        $household = Household::create([
            'name' => $data['name'],
            'owner_id' => $user->id,
            'budget_period_type' => $data['budget_period_type'],
        ]);

        $user->household_id = $household->id;
        $user->save();

        Category::create([
            'household_id' => $household->id,
            'description' => 'Moradia',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);

        session()->flash('success', 'Grupo criado com sucesso.');

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.household.create-form');
    }
}
