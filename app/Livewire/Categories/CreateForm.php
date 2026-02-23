<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\CategoryBudget;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateForm extends Component
{
    public string $description = '';
    public string $color = '#FF6B6B';
    public ?string $budget_amount = null;
    public bool $is_active = true;
    public bool $hide_from_home_chart = false;
    public ?string $default_purchase_description = null;

    public function save(): void
    {
        $this->budget_amount = $this->normalizeCurrencyValue($this->budget_amount);

        $user = auth()->user();

        $data = $this->validate([
            'description' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'hide_from_home_chart' => ['required', 'boolean'],
            'default_purchase_description' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'default_purchase_description')
                    ->where(fn ($query) => $query->where('household_id', $user?->household_id)),
            ],
        ]);

        if (! $user || $user->household_id === null) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        $category = Category::create([
            'household_id' => $user->household_id,
            'description' => $data['description'],
            'color' => $data['color'],
            'is_active' => $data['is_active'],
            'hide_from_home_chart' => $data['hide_from_home_chart'],
            'default_purchase_description' => $data['default_purchase_description'] ?: null,
        ]);

        if ($data['budget_amount'] !== null && $data['budget_amount'] !== '') {
            CategoryBudget::create([
                'category_id' => $category->id,
                'amount' => $data['budget_amount'],
                'effective_at' => now()->toDateString(),
            ]);
        }

        session()->flash('success', 'Categoria criada com sucesso.');

        $this->redirect(route('categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.categories.create-form');
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
