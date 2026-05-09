<?php

namespace Tests\Unit;

use App\Models\Household;
use App\Models\User;
use App\Support\BudgetPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_household_uses_calendar_month_even_when_household_has_legacy_period_type(): void
    {
        $user = User::factory()->create();
        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
            'budget_period_type' => 'fifth_business_day',
        ]);

        $period = BudgetPeriod::forHousehold($household, Carbon::parse('2026-04-06'));

        $this->assertSame('2026-04-01', $period['start']->toDateString());
        $this->assertSame('2026-04-30', $period['end']->toDateString());
        $this->assertSame('2026-04', $period['period_month']);
    }
}
