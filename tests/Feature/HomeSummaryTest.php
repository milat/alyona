<?php

namespace Tests\Feature;

use App\Livewire\Home\Summary;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Household;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_home_cards_show_period_totals_and_link_to_each_page(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create();
        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
        ]);
        $user->forceFill(['household_id' => $household->id])->save();

        $category = Category::create([
            'household_id' => $household->id,
            'description' => 'Mercado',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);
        $paymentMethod = PaymentMethod::create(['name' => 'Pix']);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 150,
            'effective_at' => '2026-08-01',
        ]);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 50,
            'effective_at' => '2026-09-01',
        ]);

        foreach ([['2026-08-10', 125.50], ['2026-09-10', 80]] as [$date, $amount]) {
            Purchase::create([
                'household_id' => $household->id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'title' => 'Compra',
                'amount' => $amount,
                'purchased_at' => $date,
                'reference_date' => $date,
            ]);
        }

        Livewire::actingAs($user)
            ->test(Summary::class)
            ->assertSee('Compras de Agosto')
            ->assertSee('Compras de Setembro')
            ->assertSee('Dashboard de Agosto')
            ->assertSee('Dashboard de Setembro')
            ->assertSee('R$ 125,50')
            ->assertSee('R$ 80,00')
            ->assertSee('Saldo')
            ->assertSee('R$ 24,50')
            ->assertSee('R$ -30,00')
            ->assertSee('home-balance-warning', false)
            ->assertSee('text-danger', false)
            ->assertSee('bi-bar-chart-line', false)
            ->assertSee(route('purchases.index', ['mes' => '2026-08']), false)
            ->assertSee(route('purchases.index', ['mes' => '2026-09']), false)
            ->assertSee(route('dashboard.index', ['mes' => '2026-08']), false)
            ->assertSee(route('dashboard.index', ['mes' => '2026-09']), false);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 500,
            'effective_at' => '2026-08-01',
        ]);

        Livewire::actingAs($user)
            ->test(Summary::class)
            ->assertSee('R$ 374,50')
            ->assertSee('text-success', false);
    }
}
