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

    public function test_fifth_business_day_is_calculated_correctly(): void
    {
        $fifthBusinessDay = BudgetPeriod::fifthBusinessDay(2026, 4);

        $this->assertSame('2026-04-07', $fifthBusinessDay->toDateString());
    }

    public function test_for_household_uses_previous_period_when_reference_is_before_fifth_business_day(): void
    {
        $user = User::factory()->create();
        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
            'budget_period_type' => BudgetPeriod::FIFTH_BUSINESS_DAY,
        ]);

        $period = BudgetPeriod::forHousehold($household, Carbon::parse('2026-04-06'));

        $this->assertSame('2026-03-06', $period['start']->toDateString());
        $this->assertSame('2026-04-06', $period['end']->toDateString());
        $this->assertSame('2026-03', $period['period_month']);
    }
}
