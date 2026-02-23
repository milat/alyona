<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Purchase;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class PeriodSummary extends Component
{
    public ?string $selectedMonth = null;

    #[On('purchase-saved')]
    public function refreshDashboard(): void
    {
        // Method intentionally empty: Livewire re-renders after handling the event.
    }

    public function updatedSelectedMonth(): void
    {
        // Trigger chart re-render on frontend after Livewire updates the HTML.
        $this->dispatch('dashboard-period-changed');
    }

    public function render()
    {
        $user = auth()->user();

        if (! $user || $user->household_id === null || ! $user->household) {
            return view('livewire.dashboard.period-summary', [
                'rows' => collect(),
                'chart' => ['labels' => [], 'spent' => [], 'budget' => [], 'spentColors' => []],
                'monthOptions' => collect(),
                'periodLabel' => '',
                'periodRangeLabel' => '',
                'totalSpent' => 0,
                'totalBudget' => 0,
            ]);
        }

        $household = $user->household;
        $monthOptions = $this->buildMonthOptions($household);
        $currentMonth = now()->format('Y-m');
        $hasCurrentMonth = $monthOptions->contains(fn (array $item) => $item['value'] === $currentMonth);

        if ($monthOptions->isNotEmpty()) {
            if ($this->selectedMonth === null) {
                $this->selectedMonth = $hasCurrentMonth
                    ? $currentMonth
                    : $monthOptions->first()['value'];
            } elseif (! $monthOptions->contains(fn (array $item) => $item['value'] === $this->selectedMonth)) {
                $this->selectedMonth = $hasCurrentMonth
                    ? $currentMonth
                    : $monthOptions->first()['value'];
            }
        }

        $referenceMonth = $this->selectedMonth ?: $currentMonth;
        [$year, $month] = explode('-', $referenceMonth);
        $period = BudgetPeriod::forYearMonth($household, (int) $year, (int) $month);

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->orderBy('description')
            ->get();

        $categoryIds = $categories->pluck('id');

        $spentByCategory = Purchase::query()
            ->selectRaw('category_id, SUM(amount) as total')
            ->where('household_id', $household->id)
            ->whereBetween('purchased_at', [$period['start']->toDateString(), $period['end']->toDateString()])
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $budgets = CategoryBudget::query()
            ->whereIn('category_id', $categoryIds)
            ->where(function ($query) use ($period) {
                $query->whereNull('effective_at')
                    ->orWhere('effective_at', '<=', $period['end']->toDateString());
            })
            ->orderByRaw('COALESCE(effective_at, created_at) DESC')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($items) => $items->first());

        $rows = $categories->map(function (Category $category) use ($spentByCategory, $budgets) {
            $spent = (float) ($spentByCategory[$category->id] ?? 0);
            $budget = $budgets->get($category->id)?->amount;
            $budgetValue = $budget !== null ? (float) $budget : null;
            $spentColor = '#0d6efd';

            if ($budgetValue !== null && $budgetValue > 0) {
                $ratio = ($spent / $budgetValue) * 100;

                if ($ratio < 50) {
                    $spentColor = '#0d6efd';
                } elseif ($ratio < 75) {
                    $spentColor = '#ffc107';
                } elseif ($ratio < 100) {
                    $spentColor = '#fd7e14';
                } else {
                    $spentColor = '#dc3545';
                }
            }

            return [
                'name' => $category->description,
                'color' => $category->color,
                'spent' => $spent,
                'budget' => $budgetValue,
                'remaining' => $budgetValue !== null ? $budgetValue - $spent : null,
                'spent_color' => $spentColor,
                'hide_from_home_chart' => (bool) $category->hide_from_home_chart,
            ];
        })->filter(fn (array $row) => $row['spent'] > 0 || $row['budget'] !== null)
            ->sortByDesc('spent')
            ->values();

        $chartRows = $rows->filter(fn (array $row) => ! $row['hide_from_home_chart'])->values();

        $periodMonth = Carbon::createFromFormat('Y-m', $period['period_month']);

        $chart = [
            'labels' => $chartRows->pluck('name')->values(),
            'spent' => $chartRows->pluck('spent')->map(fn ($value) => round((float) $value, 2))->values(),
            'budget' => $chartRows->pluck('budget')->map(fn ($value) => round((float) ($value ?? 0), 2))->values(),
            'spentColors' => $chartRows->pluck('spent_color')->values(),
        ];

        return view('livewire.dashboard.period-summary', [
            'rows' => $rows,
            'chart' => $chart,
            'monthOptions' => $monthOptions,
            'periodLabel' => $periodMonth->translatedFormat('F/Y'),
            'periodRangeLabel' => 'De ' . $period['start']->format('d/m/Y') . ' até ' . $period['end']->format('d/m/Y'),
            'totalSpent' => $rows->sum('spent'),
            'totalBudget' => $rows->filter(fn (array $row) => $row['budget'] !== null)->sum('budget'),
        ]);
    }

    private function buildMonthOptions($household): Collection
    {
        $windowStart = now()->copy()->subMonthsNoOverflow(12)->format('Y-m');
        $windowEnd = now()->copy()->addMonthsNoOverflow(3)->format('Y-m');
        $currentMonth = now()->format('Y-m');

        $periodMonths = Purchase::query()
            ->where('household_id', $household->id)
            ->orderByDesc('purchased_at')
            ->get(['purchased_at'])
            ->toBase()
            ->map(fn (Purchase $purchase) => BudgetPeriod::forHousehold($household, $purchase->purchased_at)['period_month'])
            ->push($currentMonth)
            ->unique()
            ->filter(fn (string $value) => $value >= $windowStart && $value <= $windowEnd)
            ->sortDesc()
            ->values();

        return $periodMonths->map(function (string $value) {
            $date = Carbon::createFromFormat('Y-m', $value);

            return [
                'value' => $value,
                'label' => $this->formatMonthLabel($date),
            ];
        });
    }

    private function formatMonthLabel(Carbon $date): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $months[(int) $date->format('n')] . ' / ' . $date->format('Y');
    }

}
