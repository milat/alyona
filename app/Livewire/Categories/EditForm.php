<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\CategoryBudget;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditForm extends Component
{
    public int $categoryId;
    public string $description = '';
    public string $color = '#FFB000';
    public ?string $budget_amount = null;
    public ?string $currentBudgetAmount = null;
    public bool $is_active = true;
    public bool $hide_from_home_chart = false;
    public ?string $default_purchase_description = null;

    public function mount(int $categoryId): void
    {
        $category = $this->getCategory($categoryId);

        $this->categoryId = $category->id;
        $this->description = $category->description;
        $this->color = $category->color;
        $this->is_active = (bool) $category->is_active;
        $this->hide_from_home_chart = (bool) $category->hide_from_home_chart;
        $this->default_purchase_description = $category->default_purchase_description;

        $currentBudget = $category->budgets()
            ->orderByDesc('effective_at')
            ->orderByDesc('created_at')
            ->first();
        $this->currentBudgetAmount = $currentBudget?->amount ? number_format((float) $currentBudget->amount, 2, '.', '') : null;
        $this->budget_amount = $this->currentBudgetAmount;
    }

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
                    ->ignore($this->categoryId)
                    ->where(fn ($query) => $query->where('household_id', $user?->household_id)),
            ],
        ]);

        $category = $this->getCategory($this->categoryId);

        $category->update([
            'description' => $data['description'],
            'color' => $data['color'],
            'is_active' => $data['is_active'],
            'hide_from_home_chart' => $data['hide_from_home_chart'],
            'default_purchase_description' => $data['default_purchase_description'] ?: null,
        ]);

        $newAmount = $data['budget_amount'] !== null && $data['budget_amount'] !== ''
            ? number_format((float) $data['budget_amount'], 2, '.', '')
            : null;

        if ($newAmount !== null && $newAmount !== $this->currentBudgetAmount) {
            CategoryBudget::create([
                'category_id' => $category->id,
                'amount' => $newAmount,
                'effective_at' => now()->toDateString(),
            ]);
        }

        session()->flash('success', 'Categoria atualizada com sucesso.');

        $this->redirect(route('categories.index'), navigate: true);
    }

    private function getCategory(int $categoryId): Category
    {
        $user = auth()->user();

        $category = Category::query()
            ->where('id', $categoryId)
            ->firstOrFail();

        if (! $user || $user->household_id === null || $category->household_id !== $user->household_id) {
            abort(404);
        }

        return $category;
    }

    public function render()
    {
        $history = $this->getCategory($this->categoryId)
            ->budgets()
            ->orderByDesc('effective_at')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.categories.edit-form', [
            'history' => $history,
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
}
