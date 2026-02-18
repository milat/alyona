<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LogoutButton extends Component
{
    public string $label = 'Sair';
    public string $class = 'dropdown-item';

    public function logout(): void
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.logout-button');
    }
}
