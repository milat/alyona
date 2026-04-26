<?php

namespace App\Livewire\Evolution;

use App\Models\Category;
use App\Models\Purchase;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url(as: 'categoria', except: null)]
    public ?string $selectedCategoryId = null;

    public function updatedSelectedCategoryId(): void
    {
        $this->dispatch('evolution-category-changed');
    }

    public function render()
    {
        $user = auth()->user();

        if (! $user || $user->household_id === null || ! $user->household) {
            return view('livewire.evolution.index', [
                'categoryOptions' => collect(),
                'chart' => ['labels' => [], 'values' => []],
            ]);
        }

        $household = $user->household;
        $categoryOptions = $this->buildCategoryOptions($user->household_id);

        if (
            $this->selectedCategoryId !== null
            && ! $categoryOptions->contains(fn (Category $category) => (string) $category->id === $this->selectedCategoryId)
        ) {
            $this->selectedCategoryId = null;
        }

        $currentPeriodMonth = BudgetPeriod::currentPeriodMonth($household);
        $periodMonths = collect(range(0, 5))
            ->map(fn (int $offset) => Carbon::createFromFormat('Y-m', $currentPeriodMonth)->subMonthsNoOverflow(5 - $offset))
            ->values();

        $chartRows = $periodMonths->map(function (Carbon $periodMonth) use ($household) {
            $period = BudgetPeriod::forYearMonth($household, (int) $periodMonth->format('Y'), (int) $periodMonth->format('m'));

            $query = Purchase::query()
                ->where('household_id', $household->id)
                ->whereBetween('purchased_at', [$period['start']->toDateString(), $period['end']->toDateString()]);

            if ($this->selectedCategoryId !== null) {
                $query->where('category_id', (int) $this->selectedCategoryId);
            }

            return [
                'label' => $this->formatMonthLabel($periodMonth),
                'total' => round((float) $query->sum('amount'), 2),
            ];
        });

        return view('livewire.evolution.index', [
            'categoryOptions' => $categoryOptions,
            'chart' => [
                'labels' => $chartRows->pluck('label')->values(),
                'values' => $chartRows->pluck('total')->values(),
            ],
        ]);
    }

    private function buildCategoryOptions(int $householdId): Collection
    {
        $recentUsageStart = now()->copy()->subDays(90)->toDateString();

        $recentUsageSubquery = Purchase::query()
            ->selectRaw('category_id, COUNT(*) as recent_usage_count')
            ->where('household_id', $householdId)
            ->whereDate('purchased_at', '>=', $recentUsageStart)
            ->groupBy('category_id');

        return Category::query()
            ->leftJoinSub($recentUsageSubquery, 'recent_purchase_usage', function ($join) {
                $join->on('categories.id', '=', 'recent_purchase_usage.category_id');
            })
            ->where('household_id', $householdId)
            ->orderByRaw('COALESCE(recent_purchase_usage.recent_usage_count, 0) DESC')
            ->orderBy('description')
            ->select('categories.*')
            ->get();
    }

    private function formatMonthLabel(Carbon $date): string
    {
        $months = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        return $months[(int) $date->format('n')] . '/' . $date->format('y');
    }
}
