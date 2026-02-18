<?php

namespace Tests\Feature;

use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\LogoutButton;
use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_is_authenticated(): void
    {
        Livewire::test(RegisterForm::class)
            ->set('name', 'Antonio')
            ->set('email', 'antonio@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $this->assertDatabaseHas('users', ['email' => 'antonio@example.com']);
        $this->assertAuthenticated();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'antonio@example.com',
            'password' => 'password123',
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'antonio@example.com')
            ->set('password', 'senha-errada')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'antonio@example.com',
            'password' => 'password123',
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'antonio@example.com')
            ->set('password', 'password123')
            ->set('remember', true)
            ->call('login');

        $this->assertAuthenticatedAs($user);

        Livewire::actingAs($user)
            ->test(LogoutButton::class)
            ->call('logout');

        $this->assertGuest();
    }
}
