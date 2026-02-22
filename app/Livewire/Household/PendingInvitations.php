<?php

namespace App\Livewire\Household;

use App\Models\HouseholdInvitation;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PendingInvitations extends Component
{
    public function accept(int $invitationId): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if ($user->household_id !== null) {
            session()->flash('success', 'Você já participa de um grupo.');
            return;
        }

        $invitation = HouseholdInvitation::query()
            ->where('id', $invitationId)
            ->where('invitee_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        DB::transaction(function () use ($user, $invitation) {
            $user->household_id = $invitation->household_id;
            $user->save();

            $invitation->status = 'accepted';
            $invitation->save();

            HouseholdInvitation::query()
                ->where('invitee_id', $user->id)
                ->where('status', 'pending')
                ->where('id', '!=', $invitation->id)
                ->update(['status' => 'rejected']);
        });

        session()->flash('success', 'Convite aceito com sucesso.');
    }

    public function reject(int $invitationId): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $invitation = HouseholdInvitation::query()
            ->where('id', $invitationId)
            ->where('invitee_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->status = 'rejected';
        $invitation->save();

        session()->flash('success', 'Convite recusado.');
    }

    public function render()
    {
        $user = auth()->user();

        $invitations = collect();

        if ($user && $user->household_id === null) {
            $invitations = HouseholdInvitation::query()
                ->with(['household', 'inviter'])
                ->where('invitee_id', $user->id)
                ->where('status', 'pending')
                ->latest()
                ->get();
        }

        return view('livewire.household.pending-invitations', [
            'invitations' => $invitations,
        ]);
    }
}
