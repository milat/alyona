<?php

namespace App\Livewire\Household;

use App\Models\HouseholdBudgetPeriodOverride;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Livewire\Component;

class PeriodStartSettingsForm extends Component
{
    public string $currentMonth = '';
    public string $nextMonth = '';
    public string $currentStartDate = '';
    public string $nextStartDate = '';

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->household || $user->household->budget_period_type !== BudgetPeriod::FIFTH_BUSINESS_DAY) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        $this->currentMonth = now()->format('Y-m');
        $this->nextMonth = now()->copy()->addMonthNoOverflow()->format('Y-m');

        $this->currentStartDate = $this->resolveMonthStartDate($user->household_id, $this->currentMonth);
        $this->nextStartDate = $this->resolveMonthStartDate($user->household_id, $this->nextMonth);
    }

    public function save(): void
    {
        $data = $this->validate([
            'currentStartDate' => ['required', 'date'],
            'nextStartDate' => ['required', 'date'],
        ]);

        $user = auth()->user();

        if (! $user || ! $user->household || $user->household->budget_period_type !== BudgetPeriod::FIFTH_BUSINESS_DAY) {
            $this->redirect(route('home'), navigate: true);
            return;
        }

        if (! $this->validateMonthConsistency($this->currentMonth, $data['currentStartDate'], 'currentStartDate')) {
            return;
        }

        if (! $this->validateMonthConsistency($this->nextMonth, $data['nextStartDate'], 'nextStartDate')) {
            return;
        }

        HouseholdBudgetPeriodOverride::updateOrCreate(
            [
                'household_id' => $user->household_id,
                'period_month' => $this->currentMonth,
            ],
            [
                'start_date' => $data['currentStartDate'],
            ]
        );

        HouseholdBudgetPeriodOverride::updateOrCreate(
            [
                'household_id' => $user->household_id,
                'period_month' => $this->nextMonth,
            ],
            [
                'start_date' => $data['nextStartDate'],
            ]
        );

        session()->flash('success', 'Configuração do 5º dia útil atualizada com sucesso.');

        $this->redirect(route('households.period-settings'), navigate: true);
    }

    private function resolveMonthStartDate(int $householdId, string $periodMonth): string
    {
        $override = HouseholdBudgetPeriodOverride::query()
            ->where('household_id', $householdId)
            ->where('period_month', $periodMonth)
            ->first();

        if ($override) {
            return $override->start_date->toDateString();
        }

        [$year, $month] = explode('-', $periodMonth);

        return BudgetPeriod::fifthBusinessDay((int) $year, (int) $month)->toDateString();
    }

    private function validateMonthConsistency(string $periodMonth, string $date, string $field): bool
    {
        $periodDate = Carbon::createFromFormat('Y-m', $periodMonth);
        $valueDate = Carbon::parse($date);

        if (
            $valueDate->year !== $periodDate->year
            || $valueDate->month !== $periodDate->month
        ) {
            $this->addError($field, 'A data deve pertencer ao mês exibido no campo.');
            return false;
        }

        return true;
    }

    public function render()
    {
        if ($this->currentMonth === '' || $this->nextMonth === '') {
            return view('livewire.household.period-start-settings-form', [
                'currentLabel' => '',
                'nextLabel' => '',
            ]);
        }

        return view('livewire.household.period-start-settings-form', [
            'currentLabel' => Carbon::createFromFormat('Y-m', $this->currentMonth)->translatedFormat('F / Y'),
            'nextLabel' => Carbon::createFromFormat('Y-m', $this->nextMonth)->translatedFormat('F / Y'),
        ]);
    }
}
