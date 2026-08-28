<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guests_cannot_access_account_management(): void
    {
        $account = User::factory()->create();

        $this->get(route('accounts.index'))->assertRedirect(route('login'));
        $this->put(route('accounts.update', $account))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_account_management(): void
    {
        $developer = $this->createUserWithRole('developer');
        $account = User::factory()->create();

        $this->actingAs($developer)->get(route('accounts.index'))->assertForbidden();
        $this->actingAs($developer)->put(route('accounts.update', $account))->assertForbidden();
    }

    public function test_qa_can_view_the_account_list(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');

        $response = $this->actingAs($qa)->get(route('accounts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/accounts/index')
            ->has('accounts', 2)
            ->has('roles')
        );
    }

    public function test_qa_can_change_a_users_role(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $response = $this->actingAs($qa)->put(route('accounts.update', $developer), [
            'role_id' => $viewerRole->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $developer->id, 'role_id' => $viewerRole->id]);
    }

    public function test_qa_can_unset_a_users_role(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');

        $response = $this->actingAs($qa)->put(route('accounts.update', $developer), [
            'role_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $developer->id, 'role_id' => null]);
    }

    public function test_role_update_fails_with_an_unknown_role(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');

        $response = $this->actingAs($qa)->put(route('accounts.update', $developer), [
            'role_id' => 9999,
        ]);

        $response->assertSessionHasErrors('role_id');
    }
}
