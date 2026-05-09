<?php

namespace App\Livewire\CreditCards;

use App\Models\CreditCard;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $user = auth()->user();
        $creditCards = collect();

        if ($user && $user->household_id !== null) {
            $creditCards = CreditCard::query()
                ->where('household_id', $user->household_id)
                ->orderByDesc('is_active')
                ->orderBy('title')
                ->get();
        }

        return view('livewire.credit-cards.index', [
            'creditCards' => $creditCards,
        ]);
    }
}
