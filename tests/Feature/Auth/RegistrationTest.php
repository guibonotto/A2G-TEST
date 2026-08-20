<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
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

    public function test_new_users_can_register_with_a_valid_role(): void
    {
        Role::create(['name' => 'QA / Tester', 'slug' => 'qa']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'qa',
        ]);

        $user = User::whereEmail('joao@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('João Silva', $user->name);
        $this->assertSame('qa', $user->role->slug);

        // RegisterResponse customizado desloga o usuário e manda para o login.
        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_registration_fails_with_an_unknown_role(): void
    {
        Role::create(['name' => 'QA / Tester', 'slug' => 'qa']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'not-a-real-role',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
    }

    public function test_registration_requires_first_and_last_name(): void
    {
        Role::create(['name' => 'QA / Tester', 'slug' => 'qa']);

        $response = $this->post(route('register.store'), [
            'email' => 'joao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'qa',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertGuest();
    }
}
