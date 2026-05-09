<?php

namespace App\Support;

use App\Models\Household;
use Carbon\Carbon;

class BudgetPeriod
{
    public const CALENDAR_MONTH = 'calendar_month';

    /**
     * @return array{start: Carbon, end: Carbon, period_month: string}
     */
    public static function forHousehold(Household $household, ?Carbon $referenceDate = null): array
    {
        $reference = ($referenceDate ?? now())->copy()->startOfDay();
        $start = $reference->copy()->startOfMonth();
        $end = $reference->copy()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'period_month' => $start->format('Y-m'),
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, period_month: string}
     */
    public static function forYearMonth(Household $household, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'period_month' => sprintf('%04d-%02d', $year, $month),
        ];
    }

    public static function currentPeriodMonth(Household $household, ?Carbon $referenceDate = null): string
    {
        return self::forHousehold($household, $referenceDate)['period_month'];
    }
}
