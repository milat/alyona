<?php

namespace Tests\Feature;

use App\Livewire\Purchases\CreateModal;
use App\Livewire\Purchases\Index;
use App\Models\Category;
use App\Models\Household;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\User;
use App\Support\BudgetPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_purchase_creates_installments_in_calendar_month_household(): void
    {
        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Lazer');
        $credit = PaymentMethod::create(['name' => 'Crédito']);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('title', 'TV')
            ->set('description', 'Compra parcelada')
            ->set('category_id', $category->id)
            ->set('payment_method_id', $credit->id)
            ->set('amount', '300,00')
            ->set('installments', '3')
            ->set('purchased_at', '2026-01-15')
            ->call('save');

        $purchases = Purchase::query()->orderBy('purchased_at')->get();

        $this->assertCount(3, $purchases);
        $this->assertSame('TV 1/3', $purchases[0]->title);
        $this->assertSame('TV 2/3', $purchases[1]->title);
        $this->assertSame('TV 3/3', $purchases[2]->title);

        $this->assertSame('2026-01-15', $purchases[0]->purchased_at->toDateString());
        $this->assertSame('2026-02-15', $purchases[1]->purchased_at->toDateString());
        $this->assertSame('2026-03-15', $purchases[2]->purchased_at->toDateString());

        $this->assertSame('100.00', $purchases[0]->amount);
        $this->assertSame('100.00', $purchases[1]->amount);
        $this->assertSame('100.00', $purchases[2]->amount);
    }

    public function test_credit_purchase_in_fifth_business_day_household_keeps_one_installment_per_period(): void
    {
        $user = $this->createUserInHousehold(BudgetPeriod::FIFTH_BUSINESS_DAY);
        $category = $this->createCategory($user, true, 'Lazer');
        $credit = PaymentMethod::create(['name' => 'Crédito']);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('title', 'Notebook')
            ->set('category_id', $category->id)
            ->set('payment_method_id', $credit->id)
            ->set('amount', '200,00')
            ->set('installments', '2')
            ->set('purchased_at', '2026-11-06')
            ->call('save');

        $purchases = Purchase::query()->orderBy('purchased_at')->get();

        $this->assertCount(2, $purchases);
        $this->assertSame('2026-11-06', $purchases[0]->purchased_at->toDateString());
        $this->assertSame('2026-12-07', $purchases[1]->purchased_at->toDateString());

        $firstPeriod = BudgetPeriod::forHousehold($user->household, $purchases[0]->purchased_at);
        $secondPeriod = BudgetPeriod::forHousehold($user->household, $purchases[1]->purchased_at);

        $this->assertSame('2026-11', $firstPeriod['period_month']);
        $this->assertSame('2026-12', $secondPeriod['period_month']);
    }

    public function test_non_credit_payment_creates_single_purchase_even_if_installments_are_informed(): void
    {
        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('title', 'Supermercado')
            ->set('category_id', $category->id)
            ->set('payment_method_id', $debit->id)
            ->set('amount', '300,00')
            ->set('installments', '5')
            ->set('purchased_at', '2026-01-10')
            ->call('save');

        $this->assertSame(1, Purchase::query()->count());
        $purchase = Purchase::query()->first();

        $this->assertNotNull($purchase);
        $this->assertSame('Supermercado', $purchase->title);
        $this->assertSame('300.00', (string) $purchase->amount);
        $this->assertSame('2026-01-10', $purchase->purchased_at->toDateString());
    }

    public function test_cannot_save_purchase_with_inactive_category(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $inactiveCategory = $this->createCategory($user, false, 'Inativa');
        $credit = PaymentMethod::create(['name' => 'Crédito']);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('title', 'Tentativa')
            ->set('category_id', $inactiveCategory->id)
            ->set('payment_method_id', $credit->id)
            ->set('amount', '10,00')
            ->set('purchased_at', '2026-01-10')
            ->call('save');
    }

    public function test_category_filter_shows_only_categories_with_purchases_in_selected_month(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 10));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $credit = PaymentMethod::create(['name' => 'Crédito']);
        $categoryJanuary = $this->createCategory($user, true, 'Categoria A');
        $categoryFebruary = $this->createCategory($user, true, 'Categoria B');

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $categoryJanuary->id,
            'payment_method_id' => $credit->id,
            'title' => 'Compra jan',
            'amount' => 10,
            'purchased_at' => '2026-01-05',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $categoryFebruary->id,
            'payment_method_id' => $credit->id,
            'title' => 'Compra fev',
            'amount' => 20,
            'purchased_at' => '2026-02-05',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->assertSee('Categoria A')
            ->assertDontSee('Categoria B');

        $this->travelBack();
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

    private function createCategory(User $user, bool $isActive, string $description): Category
    {
        return Category::create([
            'household_id' => $user->household_id,
            'description' => $description,
            'color' => '#FF6B6B',
            'is_active' => $isActive,
        ]);
    }
}
