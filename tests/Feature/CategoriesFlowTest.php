<?php

namespace Tests\Feature;

use App\Livewire\Categories\CreateForm;
use App\Livewire\Categories\EditForm;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Household;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_category_saves_active_status_and_initial_budget(): void
    {
        $user = $this->createUserInHousehold();

        Livewire::actingAs($user)
            ->test(CreateForm::class)
            ->set('description', 'Cinema')
            ->set('color', '#FF6B6B')
            ->set('is_active', true)
            ->set('budget_amount', '150,00')
            ->call('save');

        $category = Category::query()->where('description', 'Cinema')->first();

        $this->assertNotNull($category);
        $this->assertTrue((bool) $category->is_active);

        $budget = CategoryBudget::query()
            ->where('category_id', $category->id)
            ->first();

        $this->assertNotNull($budget);
        $this->assertSame('150.00', (string) $budget->amount);
        $this->assertSame(now()->toDateString(), Carbon::parse($budget->effective_at)->toDateString());
    }

    public function test_edit_category_does_not_create_new_budget_when_amount_did_not_change(): void
    {
        $user = $this->createUserInHousehold();

        $category = Category::create([
            'household_id' => $user->household_id,
            'description' => 'Cinema',
            'color' => '#FF6B6B',
            'is_active' => true,
        ]);

        CategoryBudget::create([
            'category_id' => $category->id,
            'amount' => 150.00,
            'effective_at' => now()->subMonth()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(EditForm::class, ['categoryId' => $category->id])
            ->set('description', 'Cinema e lazer')
            ->set('budget_amount', '150,00')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'description' => 'Cinema e lazer',
        ]);

        $this->assertSame(1, CategoryBudget::query()->where('category_id', $category->id)->count());
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
