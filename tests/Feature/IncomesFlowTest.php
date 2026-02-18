<?php

namespace Tests\Feature;

use App\Livewire\Incomes\CreateModal;
use App\Livewire\Incomes\EditForm;
use App\Livewire\Incomes\Index;
use App\Models\Household;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IncomesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_income_persists_data_and_normalizes_currency(): void
    {
        $user = $this->createUserInHousehold();

        Livewire::actingAs($user)
            ->test(CreateModal::class)
            ->set('description', 'Salário')
            ->set('amount', '1.234,56')
            ->set('received_at', '2026-02-10')
            ->call('save');

        $income = Income::query()
            ->where('household_id', $user->household_id)
            ->where('user_id', $user->id)
            ->where('description', 'Salário')
            ->first();

        $this->assertNotNull($income);
        $this->assertSame('1234.56', (string) $income->amount);
        $this->assertSame('2026-02-10', $income->received_at->toDateString());
    }

    public function test_edit_income_updates_record(): void
    {
        $user = $this->createUserInHousehold();

        $income = Income::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'description' => 'Freela',
            'amount' => 500,
            'received_at' => '2026-01-10',
        ]);

        Livewire::actingAs($user)
            ->test(EditForm::class, ['incomeId' => $income->id])
            ->set('description', 'Freela atualizado')
            ->set('amount', '750,00')
            ->set('received_at', '2026-01-20')
            ->call('save');

        $income->refresh();

        $this->assertSame('Freela atualizado', $income->description);
        $this->assertSame('750.00', (string) $income->amount);
        $this->assertSame('2026-01-20', $income->received_at->toDateString());
    }

    public function test_delete_income_removes_record(): void
    {
        $user = $this->createUserInHousehold();

        $income = Income::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'description' => 'Extra',
            'amount' => 100,
            'received_at' => '2026-01-10',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('delete', $income->id);

        $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
    }

    public function test_month_filter_applies_to_income_list(): void
    {
        $user = $this->createUserInHousehold();

        Income::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'description' => 'Receita A',
            'amount' => 100,
            'received_at' => '2026-01-10',
        ]);

        Income::create([
            'household_id' => $user->household_id,
            'user_id' => $user->id,
            'description' => 'Receita B',
            'amount' => 200,
            'received_at' => '2026-02-10',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedMonth', '2026-01')
            ->assertSee('Receita A')
            ->assertDontSee('Receita B');
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
