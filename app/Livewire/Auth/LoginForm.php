<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginForm extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'Credenciais invalidas.');
            return;
        }

        session()->regenerate();

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
