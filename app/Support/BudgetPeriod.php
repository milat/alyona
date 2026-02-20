<?php

namespace App\Support;

use App\Models\Household;
use App\Models\HouseholdBudgetPeriodOverride;
use Carbon\Carbon;

class BudgetPeriod
{
    public const CALENDAR_MONTH = 'calendar_month';
    public const FIFTH_BUSINESS_DAY = 'fifth_business_day';

    /** @var array<string, Carbon|null> */
    private static array $overrideCache = [];

    /**
     * @return array{start: Carbon, end: Carbon, period_month: string}
     */
    public static function forHousehold(Household $household, ?Carbon $referenceDate = null): array
    {
        $reference = ($referenceDate ?? now())->copy()->startOfDay();
        $type = $household->budget_period_type ?? self::CALENDAR_MONTH;

        if ($type !== self::FIFTH_BUSINESS_DAY) {
            $start = $reference->copy()->startOfMonth();
            $end = $reference->copy()->endOfMonth();

            return [
                'start' => $start,
                'end' => $end,
                'period_month' => $start->format('Y-m'),
            ];
        }

        $currentMonthStart = self::monthStartForHousehold($household, $reference->year, $reference->month);

        if ($reference->lt($currentMonthStart)) {
            $previous = $reference->copy()->subMonthNoOverflow();
            $periodStart = self::monthStartForHousehold($household, $previous->year, $previous->month);
            $periodEnd = $currentMonthStart->copy()->subDay();
        } else {
            $next = $reference->copy()->addMonthNoOverflow();
            $nextMonthStart = self::monthStartForHousehold($household, $next->year, $next->month);
            $periodStart = $currentMonthStart;
            $periodEnd = $nextMonthStart->copy()->subDay();
        }

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
            'period_month' => $periodStart->format('Y-m'),
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, period_month: string}
     */
    public static function forYearMonth(Household $household, int $year, int $month): array
    {
        $type = $household->budget_period_type ?? self::CALENDAR_MONTH;

        if ($type === self::FIFTH_BUSINESS_DAY) {
            $start = self::monthStartForHousehold($household, $year, $month);
            $next = Carbon::create($year, $month, 1)->addMonthNoOverflow();
            $nextStart = self::monthStartForHousehold($household, (int) $next->format('Y'), (int) $next->format('m'));

            return [
                'start' => $start,
                'end' => $nextStart->copy()->subDay(),
                'period_month' => sprintf('%04d-%02d', $year, $month),
            ];
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'period_month' => sprintf('%04d-%02d', $year, $month),
        ];
    }

    public static function fifthBusinessDay(int $year, int $month): Carbon
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();
        $businessDays = 0;

        while (true) {
            if (! $date->isWeekend()) {
                $businessDays++;

                if ($businessDays === 5) {
                    return $date->copy();
                }
            }

            $date->addDay();
        }
    }

    private static function monthStartForHousehold(Household $household, int $year, int $month): Carbon
    {
        $periodMonth = sprintf('%04d-%02d', $year, $month);
        $cacheKey = $household->id . ':' . $periodMonth;

        if (! array_key_exists($cacheKey, self::$overrideCache)) {
            $override = HouseholdBudgetPeriodOverride::query()
                ->where('household_id', $household->id)
                ->where('period_month', $periodMonth)
                ->first();

            self::$overrideCache[$cacheKey] = $override?->start_date?->copy()->startOfDay();
        }

        return self::$overrideCache[$cacheKey]?->copy()
            ?? self::fifthBusinessDay($year, $month);
    }
}
