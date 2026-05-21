<?php

namespace Tests\Feature;

use App\Livewire\Reports\PurchasesReport;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Household;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\User;
use App\Support\BudgetPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_lists_purchases_ordered_by_purchase_date(): void
    {
        $user = $this->createUserInHousehold();
        $category = $this->createCategory($user, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra antiga',
            'amount' => 10,
            'purchased_at' => '2026-05-01',
            'reference_date' => '2026-05-01',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra recente',
            'amount' => 20,
            'purchased_at' => '2026-05-10',
            'reference_date' => '2026-05-01',
        ]);

        Livewire::actingAs($user)
            ->test(PurchasesReport::class)
            ->set('dateFrom', '2026-05-01')
            ->set('dateTo', '2026-05-31')
            ->call('generate')
            ->assertSeeInOrder(['Compra recente', 'Compra antiga'])
            ->assertSee('R$ 30,00')
            ->assertDontSee('Editar')
            ->assertDontSee('Excluir');
    }


    public function test_report_requires_dates_when_fields_are_cleared(): void
    {
        $user = $this->createUserInHousehold();

        Livewire::actingAs($user)
            ->test(PurchasesReport::class)
            ->set('dateFrom', '')
            ->set('dateTo', '')
            ->call('generate')
            ->assertHasErrors(['dateFrom', 'dateTo']);
    }

    public function test_report_filters_by_inactive_category_and_credit_card_payment(): void
    {
        $user = $this->createUserInHousehold();
        $inactiveCategory = $this->createCategory($user, 'Categoria inativa', false);
        $otherCategory = $this->createCategory($user, 'Outra categoria');
        $credit = PaymentMethod::create(['name' => 'Crédito']);
        $pix = PaymentMethod::create(['name' => 'Pix']);
        $creditCard = CreditCard::create([
            'household_id' => $user->household_id,
            'title' => 'Nubank',
            'closing_day' => 10,
            'is_active' => true,
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $inactiveCategory->id,
            'payment_method_id' => $credit->id,
            'credit_card_id' => $creditCard->id,
            'title' => 'Compra filtrada',
            'amount' => 99.9,
            'purchased_at' => '2026-05-05',
            'reference_date' => '2026-05-01',
        ]);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $otherCategory->id,
            'payment_method_id' => $pix->id,
            'title' => 'Compra fora do filtro',
            'amount' => 10,
            'purchased_at' => '2026-05-05',
            'reference_date' => '2026-05-01',
        ]);

        Livewire::actingAs($user)
            ->test(PurchasesReport::class)
            ->assertSee('Categoria inativa')
            ->set('dateFrom', '2026-05-01')
            ->set('dateTo', '2026-05-31')
            ->set('selectedCategories', [(string) $inactiveCategory->id])
            ->set('selectedPayments', ['card:' . $creditCard->id])
            ->call('generate')
            ->assertSee('Compra filtrada')
            ->assertSee('Crédito (Nubank)')
            ->assertDontSee('Compra fora do filtro');
    }


    public function test_report_dates_default_to_one_month_ago_and_today(): void
    {
        $this->travelTo(now()->setDate(2026, 5, 21));

        $user = $this->createUserInHousehold();

        Livewire::actingAs($user)
            ->test(PurchasesReport::class)
            ->assertSet('dateFrom', '2026-04-21')
            ->assertSet('dateTo', '2026-05-21');

        $this->travelBack();
    }


    public function test_report_filters_by_reference_date_not_purchase_date(): void
    {
        $user = $this->createUserInHousehold();
        $category = $this->createCategory($user, 'Mercado');
        $debit = PaymentMethod::create(['name' => 'Débito']);

        Purchase::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'payment_method_id' => $debit->id,
            'title' => 'Compra com referência em junho',
            'amount' => 50,
            'purchased_at' => '2026-05-28',
            'reference_date' => '2026-06-01',
        ]);

        Livewire::actingAs($user)
            ->test(PurchasesReport::class)
            ->set('dateFrom', '2026-05-01')
            ->set('dateTo', '2026-05-31')
            ->call('generate')
            ->assertDontSee('Compra com referência em junho')
            ->set('dateFrom', '2026-06-01')
            ->set('dateTo', '2026-06-30')
            ->call('generate')
            ->assertSee('Compra com referência em junho');
    }

    public function test_report_page_is_available_from_route(): void
    {
        $user = $this->createUserInHousehold();

        $this->actingAs($user)
            ->get(route('reports.purchases'))
            ->assertOk()
            ->assertSee('Relatório');
    }

    private function createUserInHousehold(): User
    {
        $user = User::factory()->create();

        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
            'budget_period_type' => BudgetPeriod::CALENDAR_MONTH,
        ]);

        $user->forceFill(['household_id' => $household->id])->save();

        return $user->fresh();
    }

    private function createCategory(User $user, string $description, bool $isActive = true): Category
    {
        return Category::create([
            'household_id' => $user->household_id,
            'description' => $description,
            'color' => '#FF6B6B',
            'is_active' => $isActive,
        ]);
    }
}
