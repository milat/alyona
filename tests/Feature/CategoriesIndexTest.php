<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_budget_total_counts_only_active_categories(): void
    {
        $user = $this->createUserInHousehold();

        $active = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Ativa',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);

        $inactive = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Inativa',
            'color' => '#000000',
            'is_active' => false,
        ]);

        CategoryBudget::create([
            'category_id' => $active->id,
            'amount' => 120,
            'effective_at' => now()->toDateString(),
        ]);

        CategoryBudget::create([
            'category_id' => $inactive->id,
            'amount' => 999,
            'effective_at' => now()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Orçamento total:')
            ->assertSee('R$ 120,00')
            ->assertDontSee('R$ 1.119,00');
    }

    public function test_active_categories_are_listed_first(): void
    {
        $user = $this->createUserInHousehold();

        Category::create([
            'household_id' => $user->household_id,
            'description' => 'Inativa Z',
            'color' => '#111111',
            'is_active' => false,
        ]);

        Category::create([
            'household_id' => $user->household_id,
            'description' => 'Ativa A',
            'color' => '#222222',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSeeInOrder(['Ativa A', 'Inativa Z']);
    }

    private function createUserInHousehold(): User
    {
        $user = User::factory()->create();

        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
        ]);

        $user->forceFill(['household_id' => $household->id])->save();

        return $user->fresh();
    }
}
