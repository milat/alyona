<?php

namespace App\Livewire\Incomes;

use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $selectedMonth = null;

    public function delete(int $incomeId): void
    {
        $user = auth()->user();

        $income = Income::query()
            ->where('id', $incomeId)
            ->where('household_id', $user->household_id)
            ->firstOrFail();

        $income->delete();

        session()->flash('success', 'Entrada excluida com sucesso.');
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $incomes = collect();
        $monthOptions = collect();

        if ($user && $user->household_id !== null) {
            $monthOptions = $this->buildMonthOptions($user->household_id);
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

            $query = Income::query()
                ->with(['user'])
                ->where('household_id', $user->household_id)
                ->orderByDesc('created_at');

            if ($this->selectedMonth) {
                [$year, $month] = explode('-', $this->selectedMonth);
                $query->whereYear('received_at', (int) $year)
                    ->whereMonth('received_at', (int) $month);
            }

            $incomes = $query->paginate(10);
        }

        return view('livewire.incomes.index', [
            'incomes' => $incomes,
            'monthOptions' => $monthOptions,
        ]);
    }

    private function buildMonthOptions(int $householdId): Collection
    {
        $windowStart = now()->copy()->subMonthsNoOverflow(12)->startOfMonth();
        $windowEnd = now()->copy()->addMonthsNoOverflow(12)->endOfMonth();

        $rawMonths = Income::query()
            ->where('household_id', $householdId)
            ->whereBetween('received_at', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->orderByDesc('received_at')
            ->get(['received_at'])
            ->toBase()
            ->map(fn (Income $income) => $income->received_at->format('Y-m'))
            ->unique()
            ->values();

        return $rawMonths->map(function (string $value) {
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
