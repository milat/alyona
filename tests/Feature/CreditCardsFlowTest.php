<?php

namespace Tests\Feature;

use App\Livewire\CreditCards\CreateForm;
use App\Livewire\CreditCards\EditForm;
use App\Models\CreditCard;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditCardsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_credit_card(): void
    {
        $user = $this->createUserInHousehold();

        Livewire::actingAs($user)
            ->test(CreateForm::class)
            ->set('title', 'Nubank')
            ->set('closing_day', '10')
            ->set('limit', '1.234,56')
            ->set('observation', 'Cartão principal')
            ->set('is_active', true)
            ->call('save');

        $creditCard = CreditCard::query()->first();

        $this->assertNotNull($creditCard);
        $this->assertSame($user->household_id, $creditCard->household_id);
        $this->assertSame('Nubank', $creditCard->title);
        $this->assertSame(10, $creditCard->closing_day);
        $this->assertSame('1234.56', $creditCard->limit);
        $this->assertSame('Cartão principal', $creditCard->observation);
        $this->assertTrue($creditCard->is_active);
    }

    public function test_user_can_update_credit_card(): void
    {
        $user = $this->createUserInHousehold();
        $creditCard = CreditCard::create([
            'household_id' => $user->household_id,
            'title' => 'Visa',
            'closing_day' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(EditForm::class, ['creditCardId' => $creditCard->id])
            ->set('title', 'Visa Black')
            ->set('closing_day', '12')
            ->set('limit', '500,00')
            ->set('is_active', false)
            ->call('save');

        $creditCard->refresh();

        $this->assertSame('Visa Black', $creditCard->title);
        $this->assertSame(12, $creditCard->closing_day);
        $this->assertSame('500.00', $creditCard->limit);
        $this->assertFalse($creditCard->is_active);
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
