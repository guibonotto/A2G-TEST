<?php

namespace Tests\Feature;

use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RequirementControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guests_cannot_access_requirement_management(): void
    {
        $requirement = Requirement::create(['code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);

        $this->get(route('requirements.index'))->assertRedirect(route('login'));
        $this->post(route('requirements.store'))->assertRedirect(route('login'));
        $this->put(route('requirements.update', $requirement))->assertRedirect(route('login'));
        $this->delete(route('requirements.destroy', $requirement))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_requirement_management(): void
    {
        $developer = $this->createUserWithRole('developer');
        $requirement = Requirement::create(['code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);

        $this->actingAs($developer)->get(route('requirements.index'))->assertForbidden();
        $this->actingAs($developer)->post(route('requirements.store'))->assertForbidden();
        $this->actingAs($developer)->put(route('requirements.update', $requirement))->assertForbidden();
        $this->actingAs($developer)->delete(route('requirements.destroy', $requirement))->assertForbidden();
    }

    public function test_qa_can_view_the_requirement_list(): void
    {
        $qa = $this->createUserWithRole('qa');
        Requirement::create(['code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro de Caso de Teste']);

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('management/requirements/index')
                ->has('requirements', 1)
                ->where('requirements.0.code', 'RF001')
                ->where('requirements.0.test_cases_count', 0)
            );
    }

    public function test_qa_can_create_a_requirement(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF032',
            'type' => 'funcional',
            'title' => 'Novo requisito',
            'description' => 'Descrição do requisito.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requirements', ['code' => 'RF032', 'created_by' => $qa->id]);
    }

    public function test_requirement_creation_fails_with_duplicate_code(): void
    {
        $qa = $this->createUserWithRole('qa');
        Requirement::create(['code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);

        $response = $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_requirement_creation_fails_with_invalid_type(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF033',
            'type' => 'inexistente',
            'title' => 'Requisito inválido',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_qa_can_update_a_requirement(): void
    {
        $qa = $this->createUserWithRole('qa');
        $requirement = Requirement::create(['code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);

        $response = $this->actingAs($qa)->put(route('requirements.update', $requirement), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Cadastro de Caso de Teste',
            'priority' => 'alta',
            'status' => 'em_andamento',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requirements', [
            'id' => $requirement->id,
            'title' => 'Cadastro de Caso de Teste',
            'priority' => 'alta',
            'status' => 'em_andamento',
        ]);
    }

    public function test_qa_can_delete_a_requirement(): void
    {
        $qa = $this->createUserWithRole('qa');
        $requirement = Requirement::create(['code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);

        $response = $this->actingAs($qa)->delete(route('requirements.destroy', $requirement));

        $response->assertRedirect();
        $this->assertDatabaseMissing('requirements', ['id' => $requirement->id]);
    }
}
