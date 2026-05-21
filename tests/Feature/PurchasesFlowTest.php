<?php

namespace Tests\Feature;

use App\Livewire\Purchases\CreateModal;
use App\Livewire\Purchases\EditForm;
use App\Livewire\Purchases\Index;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Household;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchaseGroup;
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
        PaymentMethod::create(['name' => 'Crédito']);
        $creditCard = CreditCard::create([
            'household_id' => $user->household_id,
            'title' => 'Nubank',
            'closing_day' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('title', 'TV')
            ->set('description', 'Compra parcelada')
            ->set('category_id', $category->id)
            ->set('payment_option', 'card:' . $creditCard->id)
            ->set('amount', '300,00')
            ->set('installments', '3')
            ->set('purchased_at', '2026-01-15')
            ->call('save');

        $purchases = Purchase::query()->orderBy('id')->get();

        $this->assertCount(3, $purchases);
        $this->assertSame('TV 1/3', $purchases[0]->title);
        $this->assertSame('TV 2/3', $purchases[1]->title);
        $this->assertSame('TV 3/3', $purchases[2]->title);

        $this->assertSame('2026-01-15', $purchases[0]->purchased_at->toDateString());
        $this->assertSame('2026-01-15', $purchases[1]->purchased_at->toDateString());
        $this->assertSame('2026-01-15', $purchases[2]->purchased_at->toDateString());
        $this->assertSame('2026-02-01', $purchases[0]->reference_date->toDateString());
        $this->assertSame('2026-03-01', $purchases[1]->reference_date->toDateString());
        $this->assertSame('2026-04-01', $purchases[2]->reference_date->toDateString());

        $this->assertSame('100.00', $purchases[0]->amount);
        $this->assertSame('100.00', $purchases[1]->amount);
        $this->assertSame('100.00', $purchases[2]->amount);
        $this->assertTrue($purchases->every(fn (Purchase $purchase) => $purchase->credit_card_id === $creditCard->id));
    }

    public function test_credit_purchase_in_legacy_household_uses_calendar_month_installments(): void
    {
        $user = $this->createUserInHousehold('fifth_business_day');
        $category = $this->createCategory($user, true, 'Lazer');
        PaymentMethod::create(['name' => 'Crédito']);
        $creditCard = CreditCard::create([
            'household_id' => $user->household_id,
            'title' => 'Visa',
            'closing_day' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('title', 'Notebook')
            ->set('category_id', $category->id)
            ->set('payment_option', 'card:' . $creditCard->id)
            ->set('amount', '200,00')
            ->set('installments', '2')
            ->set('purchased_at', '2026-11-06')
            ->call('save');

        $purchases = Purchase::query()->orderBy('id')->get();

        $this->assertCount(2, $purchases);
        $this->assertSame('2026-11-06', $purchases[0]->purchased_at->toDateString());
        $this->assertSame('2026-11-06', $purchases[1]->purchased_at->toDateString());
        $this->assertSame('2026-12-01', $purchases[0]->reference_date->toDateString());
        $this->assertSame('2027-01-01', $purchases[1]->reference_date->toDateString());

        $firstPeriod = BudgetPeriod::forHousehold($user->household, $purchases[0]->reference_date);
        $secondPeriod = BudgetPeriod::forHousehold($user->household, $purchases[1]->reference_date);

        $this->assertSame('2026-12', $firstPeriod['period_month']);
        $this->assertSame('2027-01', $secondPeriod['period_month']);
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
            ->set('payment_option', 'method:' . $debit->id)
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
        $this->assertSame('2026-01-01', $purchase->reference_date->toDateString());
    }

    public function test_user_can_edit_purchase(): void
    {
        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $oldCategory = $this->createCategory($user, true, 'Mercado');
        $newCategory = $this->createCategory($user, true, 'Lazer');
        $debit = PaymentMethod::create(['name' => 'Débito']);
        PaymentMethod::create(['name' => 'Crédito']);
        $creditCard = CreditCard::create([
            'household_id' => $user->household_id,
            'title' => 'Nubank',
            'closing_day' => 10,
            'is_active' => true,
        ]);

        $purchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $oldCategory->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra antiga',
            'amount' => 25,
            'purchased_at' => '2026-01-10',
        ]);

        Livewire::actingAs($user)
            ->test(EditForm::class, ['purchaseId' => $purchase->id])
            ->set('title', 'Compra atualizada')
            ->set('description', 'Observação nova')
            ->set('category_id', $newCategory->id)
            ->set('payment_option', 'card:' . $creditCard->id)
            ->set('amount', '99,90')
            ->set('purchased_at', '2026-02-12')
            ->call('save');

        $purchase->refresh();

        $this->assertSame('Compra atualizada', $purchase->title);
        $this->assertSame('Observação nova', $purchase->description);
        $this->assertSame($newCategory->id, $purchase->category_id);
        $this->assertSame($creditCard->id, $purchase->credit_card_id);
        $this->assertSame('99.90', $purchase->amount);
        $this->assertSame('2026-02-12', $purchase->purchased_at->toDateString());
        $this->assertSame('2026-03-01', $purchase->reference_date->toDateString());
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
            ->set('payment_option', 'method:' . $credit->id)
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

    public function test_search_filters_purchases_by_date_title_category_payment_and_amount(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 10));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $pharmacy = $this->createCategory($user, true, 'Farmácia');
        $market = $this->createCategory($user, true, 'Mercado');
        $pix = PaymentMethod::create(['name' => 'Pix']);
        $debit = PaymentMethod::create(['name' => 'Débito']);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $pharmacy->id,
            'payment_method_id' => $pix->id,
            'title' => 'Remédio infantil',
            'amount' => 3.42,
            'purchased_at' => '2026-01-10',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $market->id,
            'payment_method_id' => $debit->id,
            'title' => 'Supermercado semanal',
            'amount' => 250,
            'purchased_at' => '2026-01-15',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('searchInput', 'Remédio')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal')
            ->set('searchInput', 'Farmácia')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal')
            ->set('searchInput', 'Pix')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal')
            ->set('searchInput', '3,42')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal')
            ->set('searchInput', '10/01/2026')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal')
            ->set('searchInput', '10/01')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal')
            ->set('searchInput', '10')
            ->call('applyFilters')
            ->assertSee('Remédio infantil')
            ->assertDontSee('Supermercado semanal');

        $this->travelBack();
    }


    public function test_sorting_keeps_search_filters_applied(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 10));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Mercado');
        $pix = PaymentMethod::create(['name' => 'Pix']);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $pix->id,
            'title' => 'Compra maior',
            'amount' => 50,
            'purchased_at' => '2026-01-10',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $pix->id,
            'title' => 'Compra menor',
            'amount' => 10,
            'purchased_at' => '2026-01-08',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $pix->id,
            'title' => 'Outro lançamento',
            'amount' => 1,
            'purchased_at' => '2026-01-07',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('searchInput', 'Compra')
            ->call('applyFilters')
            ->set('sortByInput', 'amount')
            ->set('sortDirectionInput', 'asc')
            ->call('applySort')
            ->assertSeeInOrder(['Compra menor', 'Compra maior'])
            ->assertDontSee('Outro lançamento');

        $this->travelBack();
    }


    public function test_payment_options_are_ordered_by_last_30_days_usage(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 31));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);
        $pix = PaymentMethod::create(['name' => 'Pix']);
        $cash = PaymentMethod::create(['name' => 'Em espécie']);
        $credit = PaymentMethod::create(['name' => 'Crédito']);
        $creditCard = CreditCard::create([
            'household_id' => $user->household_id,
            'title' => 'Nubank',
            'closing_day' => 10,
            'is_active' => true,
        ]);

        foreach ([1, 2, 3] as $day) {
            Purchase::create([
                'household_id' => $user->household_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'payment_method_id' => $pix->id,
                'title' => 'Pix ' . $day,
                'amount' => 10,
                'purchased_at' => '2026-01-0' . $day,
            ]);
        }

        foreach ([4, 5] as $day) {
            Purchase::create([
                'household_id' => $user->household_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'payment_method_id' => $credit->id,
                'credit_card_id' => $creditCard->id,
                'title' => 'Crédito ' . $day,
                'amount' => 10,
                'purchased_at' => '2026-01-0' . $day,
            ]);
        }

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Débito recente',
            'amount' => 10,
            'purchased_at' => '2026-01-06',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $cash->id,
            'title' => 'Compra antiga',
            'amount' => 10,
            'purchased_at' => '2025-12-01',
        ]);

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->assertSeeInOrder(['Pix', 'Crédito (Nubank)', 'Débito', 'Em espécie']);

        $this->travelBack();
    }


    public function test_purchase_category_filter_options_are_ordered_by_last_90_days_usage(): void
    {
        $this->travelTo(now()->setDate(2026, 4, 15));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $debit = PaymentMethod::create(['name' => 'Débito']);
        $frequentCategory = $this->createCategory($user, true, 'Mais usada');
        $lessUsedCategory = $this->createCategory($user, true, 'Menos usada');

        foreach ([1, 2, 3] as $day) {
            Purchase::create([
                'household_id' => $user->household_id,
                'user_id' => $user->id,
                'category_id' => $frequentCategory->id,
                'payment_method_id' => $debit->id,
                'title' => 'Compra frequente ' . $day,
                'amount' => 10,
                'purchased_at' => '2026-04-0' . $day,
                'reference_date' => '2026-04-01',
            ]);
        }

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $lessUsedCategory->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra menos usada',
            'amount' => 10,
            'purchased_at' => '2026-04-10',
            'reference_date' => '2026-04-01',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-04')
            ->assertSeeInOrder(['Mais usada', 'Menos usada']);

        $this->travelBack();
    }


    public function test_user_can_group_and_ungroup_purchases(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 10));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);

        $firstPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra 1',
            'amount' => 40,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        $secondPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra 2',
            'amount' => 60,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('selectedPurchaseIds', [(string) $firstPurchase->id, (string) $secondPurchase->id])
            ->call('groupSelectedPurchases')
            ->assertHasNoErrors()
            ->assertSee('AGRP')
            ->assertSee('R$ 100,00');

        $firstPurchase->refresh();
        $secondPurchase->refresh();

        $this->assertNotNull($firstPurchase->purchase_group_id);
        $this->assertSame($firstPurchase->purchase_group_id, $secondPurchase->purchase_group_id);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('selectedPurchaseIds', [(string) $firstPurchase->id, (string) $secondPurchase->id])
            ->call('ungroupSelectedPurchases')
            ->assertHasNoErrors();

        $this->assertNull($firstPurchase->refresh()->purchase_group_id);
        $this->assertNull($secondPurchase->refresh()->purchase_group_id);
        $this->assertSame(0, PurchaseGroup::query()->count());

        $this->travelBack();
    }


    public function test_grouping_buttons_follow_selected_purchase_type(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 10));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);
        $group = PurchaseGroup::create(['household_id' => $user->household_id]);

        $groupedPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'purchase_group_id' => $group->id,
            'title' => 'Compra agrupada',
            'amount' => 40,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        $firstUngroupedPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra livre 1',
            'amount' => 60,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        $secondUngroupedPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra livre 2',
            'amount' => 30,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('selectedPurchaseIds', [(string) $groupedPurchase->id])
            ->assertSee('Desagrupar')
            ->assertDontSee('Agrupar</button>', false);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('selectedPurchaseIds', [(string) $firstUngroupedPurchase->id])
            ->assertDontSee('Agrupar</button>', false)
            ->assertDontSee('Desagrupar')
            ->set('selectedPurchaseIds', [(string) $firstUngroupedPurchase->id, (string) $secondUngroupedPurchase->id])
            ->assertSee('Agrupar')
            ->assertDontSee('Desagrupar');

        $this->travelBack();
    }

    public function test_purchase_already_in_group_cannot_be_grouped_again(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 10));

        $user = $this->createUserInHousehold(BudgetPeriod::CALENDAR_MONTH);
        $category = $this->createCategory($user, true, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);
        $group = PurchaseGroup::create(['household_id' => $user->household_id]);

        $groupedPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'purchase_group_id' => $group->id,
            'title' => 'Compra agrupada',
            'amount' => 40,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        $newPurchase = Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra nova',
            'amount' => 60,
            'purchased_at' => '2026-01-10',
            'reference_date' => '2026-01-01',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->set('selectedPurchaseIds', [(string) $groupedPurchase->id, (string) $newPurchase->id])
            ->call('groupSelectedPurchases')
            ->assertHasErrors(['selectedPurchaseIds']);

        $this->assertNull($newPurchase->refresh()->purchase_group_id);
        $this->assertSame(1, PurchaseGroup::query()->count());

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
