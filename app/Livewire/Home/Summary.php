<?php

namespace App\Livewire\Home;

use App\Models\CategoryBudget;
use App\Models\Purchase;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class Summary extends Component
{
    #[On('purchase-saved')]
    public function refreshSummary(): void
    {
        // Livewire re-renders the cards after a purchase is saved.
    }

    public function render()
    {
        $user = auth()->user();
        $cards = collect();

        if ($user?->household_id && $user->household) {
            $currentMonth = BudgetPeriod::currentPeriodMonth($user->household);
            $currentDate = Carbon::createFromFormat('Y-m', $currentMonth);
            $nextDate = $currentDate->copy()->addMonthNoOverflow();

            $months = collect([
                ['date' => $currentDate],
                ['date' => $nextDate],
            ])->map(function (array $item) use ($user) {
                $month = $item['date']->format('Y-m');
                $period = BudgetPeriod::forYearMonth(
                    $user->household,
                    (int) $item['date']->format('Y'),
                    (int) $item['date']->format('m'),
                );

                $spent = (float) Purchase::query()
                    ->where('household_id', $user->household_id)
                    ->whereBetween('reference_date', [
                        $period['start']->toDateString(),
                        $period['end']->toDateString(),
                    ])
                    ->sum('amount');

                $budget = (float) CategoryBudget::query()
                    ->whereHas('category', fn ($query) => $query->where('household_id', $user->household_id))
                    ->where(function ($query) use ($period) {
                        $query->whereNull('effective_at')
                            ->orWhere('effective_at', '<=', $period['end']->toDateString());
                    })
                    ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('category_id')
                    ->sum(fn ($budgets) => (float) $budgets->first()->amount);

                return [
                    'month' => $month,
                    'name' => $this->formatMonthName($item['date']),
                    'spent' => $spent,
                    'budget' => $budget,
                    'balance' => $budget - $spent,
                ];
            });

            foreach ($months as $month) {
                $cards->push([
                    'title' => 'Compras de ' . $month['name'],
                    'icon' => 'bi-cart',
                    'url' => route('purchases.index', ['mes' => $month['month']]),
                    'total' => $month['spent'],
                    'value_label' => null,
                    'value_class' => 'text-body',
                ]);
            }

            foreach ($months as $month) {
                $cards->push([
                    'title' => 'Dashboard de ' . $month['name'],
                    'icon' => 'bi-bar-chart-line',
                    'url' => route('dashboard.index', ['mes' => $month['month']]),
                    'total' => $month['balance'],
                    'value_label' => 'Saldo',
                    'value_class' => $this->balanceColorClass($month['balance'], $month['budget']),
                ]);
            }
        }

        return view('livewire.home.summary', ['cards' => $cards]);
    }

    private function balanceColorClass(float $balance, float $budget): string
    {
        if ($balance < 0) {
            return 'text-danger';
        }

        if ($budget > 0 && $balance <= $budget * 0.2) {
            return 'home-balance-warning';
        }

        return 'text-success';
    }

    private function formatMonthName(Carbon $date): string
    {
        return [
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
        ][(int) $date->format('n')];
    }
}
