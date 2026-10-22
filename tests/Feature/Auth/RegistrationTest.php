<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_register_without_an_access_role(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::whereEmail('joao@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('João Silva', $user->name);
        $this->assertSame(0, $user->projects()->count());

        // RegisterResponse customizado desloga o usuário e manda para o login.
        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_registration_ignores_a_submitted_access_role(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
        $this->assertDatabaseCount('project_user', 0);
    }

    public function test_registration_requires_first_and_last_name(): void
    {
        $response = $this->post(route('register.store'), [
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertGuest();
    }
}
