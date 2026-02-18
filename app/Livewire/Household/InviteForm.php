<?php

namespace App\Livewire\Household;

use App\Models\HouseholdInvitation;
use App\Models\User;
use Livewire\Component;

class InviteForm extends Component
{
    public string $email = '';

    public function send(): void
    {
        $data = $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = auth()->user();

        if ($user->household_id === null) {
            $this->addError('email', 'Voce precisa ter um grupo para convidar alguem.');
            return;
        }

        $invitee = User::where('email', $data['email'])->first();

        if (! $invitee) {
            $this->addError('email', 'Usuario nao encontrado.');
            return;
        }

        if ($invitee->id === $user->id) {
            $this->addError('email', 'Voce nao pode convidar a si mesmo.');
            return;
        }

        if ($invitee->household_id !== null) {
            $this->addError('email', 'O usuario ja possui um grupo.');
            return;
        }

        $existing = HouseholdInvitation::where('household_id', $user->household_id)
            ->where('invitee_id', $invitee->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            $this->addError('email', 'Ja existe um convite pendente para este usuario.');
            return;
        }

        HouseholdInvitation::create([
            'household_id' => $user->household_id,
            'inviter_id' => $user->id,
            'invitee_id' => $invitee->id,
            'status' => 'pending',
        ]);

        $this->reset('email');
        session()->flash('success', 'Convite enviado com sucesso.');

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.household.invite-form');
    }
}
