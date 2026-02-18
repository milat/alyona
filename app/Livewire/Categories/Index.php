<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $user = auth()->user();
        $categories = collect();

        if ($user && $user->household_id !== null) {
            $categories = Category::query()
                ->where('household_id', $user->household_id)
                ->with(['budgets' => function ($query) {
                    $query->orderByDesc('effective_at')->orderByDesc('created_at');
                }])
                ->orderByDesc('is_active')
                ->orderBy('description')
                ->get();
        }

        $activeBudgetTotal = $categories
            ->filter(fn (Category $category) => $category->is_active)
            ->sum(fn (Category $category) => (float) ($category->budgets->first()?->amount ?? 0));

        return view('livewire.categories.index', [
            'categories' => $categories,
            'activeBudgetTotal' => $activeBudgetTotal,
        ]);
    }
}
