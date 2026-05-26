<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\PeriodSummary;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Household;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchaseCategoryAllocation;
use App\Models\User;
use App\Support\BudgetPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPeriodSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_budget_effective_on_period_end(): void
    {
        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Cinema',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 100,
            'effective_at' => '2026-01-01',
        ]);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 150,
            'effective_at' => '2026-03-01',
        ]);

        $payment = PaymentMethod::create(['name' => 'Pix']);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $payment->id,
            'title' => 'Ingresso',
            'amount' => 80,
            'purchased_at' => '2026-01-15',
        ]);

        Livewire::actingAs($user)
            ->test(PeriodSummary::class)
            ->set('selectedMonth', '2026-01')
            ->assertSee('Cinema')
            ->assertSee('R$ 80,00')
            ->assertSee('R$ 100,00')
            ->assertDontSee('R$ 150,00');
    }

    public function test_dashboard_uses_calendar_month_boundaries_for_legacy_households(): void
    {
        $user = $this->createUserInHousehold('fifth_business_day');

        $category = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Mercado',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 1000,
            'effective_at' => '2026-02-01',
        ]);

        $payment = PaymentMethod::create(['name' => 'Débito']);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $payment->id,
            'title' => 'Compra de marco',
            'amount' => 100,
            'purchased_at' => '2026-03-04',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $payment->id,
            'title' => 'Compra do período de março',
            'amount' => 200,
            'purchased_at' => '2026-03-06',
        ]);

        Livewire::actingAs($user)
            ->test(PeriodSummary::class)
            ->set('selectedMonth', '2026-03')
            ->assertSee('R$ 300,00');
    }


    public function test_dashboard_splits_purchase_amount_between_main_category_and_subcategories(): void
    {
        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $mainCategory = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Mercado',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);
        $subCategory = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Farmácia',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);
        $secondSubCategory = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Lazer',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);
        $payment = PaymentMethod::create(['name' => 'Pix']);

        $purchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $mainCategory->id,
            'payment_method_id' => $payment->id,
            'title' => 'Compra dividida',
            'amount' => 100,
            'purchased_at' => '2026-01-15',
            'reference_date' => '2026-01-01',
        ]);

        PurchaseCategoryAllocation::create([
            'purchase_id' => $purchase->id,
            'category_id' => $subCategory->id,
            'amount' => 30,
        ]);

        PurchaseCategoryAllocation::create([
            'purchase_id' => $purchase->id,
            'category_id' => $secondSubCategory->id,
            'amount' => 20,
        ]);

        Livewire::actingAs($user)
            ->test(PeriodSummary::class)
            ->set('selectedMonth', '2026-01')
            ->assertSee('Mercado')
            ->assertSee('R$ 50,00')
            ->assertSee('Farmácia')
            ->assertSee('R$ 30,00')
            ->assertSee('Lazer')
            ->assertSee('R$ 20,00');
    }

    private function createUserInHousehold(string $budgetPeriodType): User
    {
        $user = User::factory()->create();

        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
            'budget_period_type' => $budgetPeriodType,
        ]);

        $user->forceFill(['household_id' => $household->id])->save();

        return $user->fresh();
    }
}
