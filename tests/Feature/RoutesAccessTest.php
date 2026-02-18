<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Household;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_home_and_register_but_is_redirected_from_protected_routes(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('register'))->assertOk();

        $this->get(route('categories.index'))->assertRedirect(route('login'));
        $this->get(route('purchases.index'))->assertRedirect(route('login'));
        $this->get(route('incomes.index'))->assertRedirect(route('login'));
        $this->get(route('households.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create();
        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
        ]);

        $user->forceFill(['household_id' => $household->id])->save();

        $category = Category::create([
            'household_id' => $household->id,
            'description' => 'Lazer',
            'color' => '#FFFFFF',
            'is_active' => true,
        ]);

        $income = Income::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'description' => 'Salário',
            'amount' => 1000,
            'received_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('categories.create'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('categories.edit', $category))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('purchases.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('incomes.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('incomes.edit', $income))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('households.invitations.create'))
            ->assertOk();
    }
}
