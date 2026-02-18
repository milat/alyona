<?php

namespace Tests\Feature;

use App\Livewire\Household\CreateForm;
use App\Livewire\Household\InviteForm;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\User;
use App\Support\BudgetPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseholdFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_household_and_default_category_is_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateForm::class)
            ->set('name', 'Familia Silva')
            ->set('budget_period_type', BudgetPeriod::FIFTH_BUSINESS_DAY)
            ->call('create');

        $user->refresh();

        $this->assertNotNull($user->household_id);

        $this->assertDatabaseHas('households', [
            'id' => $user->household_id,
            'name' => 'Familia Silva',
            'budget_period_type' => BudgetPeriod::FIFTH_BUSINESS_DAY,
        ]);

        $this->assertDatabaseHas('categories', [
            'household_id' => $user->household_id,
            'description' => 'Moradia',
            'color' => '#FFFFFF',
            'is_active' => 1,
        ]);
    }

    public function test_user_cannot_create_second_household(): void
    {
        $user = User::factory()->create();
        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Existente',
        ]);
        $user->forceFill(['household_id' => $household->id])->save();

        Livewire::actingAs($user)
            ->test(CreateForm::class)
            ->set('name', 'Novo grupo')
            ->call('create');

        $this->assertSame(1, Household::query()->count());
        $this->assertDatabaseMissing('households', ['name' => 'Novo grupo']);
    }

    public function test_user_can_send_invitation(): void
    {
        [$inviter, $household] = $this->createUserAndHousehold();
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        Livewire::actingAs($inviter)
            ->test(InviteForm::class)
            ->set('email', $invitee->email)
            ->call('send');

        $this->assertDatabaseHas('household_invitations', [
            'household_id' => $household->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee->id,
            'status' => 'pending',
        ]);
    }

    public function test_invitation_validations_are_enforced(): void
    {
        [$inviter, $household] = $this->createUserAndHousehold();

        $existingMember = User::factory()->create(['email' => 'member@example.com']);
        $existingMember->forceFill(['household_id' => $household->id])->save();

        $other = User::factory()->create(['email' => 'other@example.com']);

        HouseholdInvitation::create([
            'household_id' => $household->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $other->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($inviter)
            ->test(InviteForm::class)
            ->set('email', 'naoexiste@example.com')
            ->call('send')
            ->assertHasErrors(['email']);

        Livewire::actingAs($inviter)
            ->test(InviteForm::class)
            ->set('email', $inviter->email)
            ->call('send')
            ->assertHasErrors(['email']);

        Livewire::actingAs($inviter)
            ->test(InviteForm::class)
            ->set('email', $existingMember->email)
            ->call('send')
            ->assertHasErrors(['email']);

        Livewire::actingAs($inviter)
            ->test(InviteForm::class)
            ->set('email', $other->email)
            ->call('send')
            ->assertHasErrors(['email']);
    }

    public function test_user_without_household_cannot_send_invitation(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['email' => 'target@example.com']);

        Livewire::actingAs($user)
            ->test(InviteForm::class)
            ->set('email', $target->email)
            ->call('send')
            ->assertHasErrors(['email']);
    }

    private function createUserAndHousehold(): array
    {
        $user = User::factory()->create();

        $household = Household::create([
            'owner_id' => $user->id,
            'name' => 'Casa',
        ]);

        $user->forceFill(['household_id' => $household->id])->save();

        return [$user->fresh(), $household->fresh()];
    }
}
