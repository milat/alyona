<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\Purchase;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $selectedMonth = null;
    public ?string $selectedCategoryId = null;

    public function delete(int $purchaseId): void
    {
        $user = auth()->user();

        $purchase = Purchase::query()
            ->where('id', $purchaseId)
            ->where('household_id', $user->household_id)
            ->firstOrFail();

        $purchase->delete();

        session()->flash('success', 'Compra excluida com sucesso.');
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategoryId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $purchases = collect();
        $monthOptions = collect();
        $categoryOptions = collect();

        if ($user && $user->household_id !== null) {
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

            $categoryIdsWithPurchases = collect();

            if ($this->selectedMonth) {
                [$year, $month] = explode('-', $this->selectedMonth);
                $period = BudgetPeriod::forYearMonth($household, (int) $year, (int) $month);

                $categoryIdsWithPurchases = Purchase::query()
                    ->where('household_id', $user->household_id)
                    ->whereBetween('purchased_at', [
                        $period['start']->toDateString(),
                        $period['end']->toDateString(),
                    ])
                    ->distinct()
                    ->pluck('category_id');
            }

            $categoryOptions = Category::query()
                ->where('household_id', $user->household_id)
                ->whereIn('id', $categoryIdsWithPurchases)
                ->orderByDesc('is_active')
                ->orderBy('description')
                ->get(['id', 'description', 'is_active']);

            if (
                $this->selectedCategoryId !== null
                && ! $categoryOptions->contains(fn (Category $category) => (string) $category->id === $this->selectedCategoryId)
            ) {
                $this->selectedCategoryId = null;
            }

            $query = Purchase::query()
                ->with(['category', 'paymentMethod', 'user'])
                ->where('household_id', $user->household_id)
                ->orderByDesc('created_at');

            if ($this->selectedMonth) {
                [$year, $month] = explode('-', $this->selectedMonth);
                $period = BudgetPeriod::forYearMonth($household, (int) $year, (int) $month);
                $query->whereBetween('purchased_at', [
                    $period['start']->toDateString(),
                    $period['end']->toDateString(),
                ]);
            }

            if ($this->selectedCategoryId !== null) {
                $query->where('category_id', (int) $this->selectedCategoryId);
            }

            $purchases = $query->paginate(10);
        }

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'monthOptions' => $monthOptions,
            'categoryOptions' => $categoryOptions,
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
