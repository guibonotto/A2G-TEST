<?php

namespace Tests\Feature;

use App\Enums\TestCaseStatus;
use App\Models\Classification;
use App\Models\Role;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestCaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guests_cannot_access_test_cases(): void
    {
        $classification = Classification::create(['name' => 'Funcional']);
        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get(route('test-cases.index'))->assertRedirect(route('login'));
        $this->get(route('test-cases.create'))->assertRedirect(route('login'));
        $this->get(route('test-cases.show', $testCase))->assertRedirect(route('login'));
        $this->get(route('test-cases.edit', $testCase))->assertRedirect(route('login'));
        $this->delete(route('test-cases.delete', $testCase))->assertRedirect(route('login'));
    }

    public function test_index_lists_existing_test_cases(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Acessar tela de login']);

        $response = $this->actingAs($user)->get(route('test-cases.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.title', 'Login com credenciais válidas')
            ->where('testCases.0.steps_count', 1)
        );
    }

    public function test_index_can_be_filtered_by_search_term(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $matching = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['search' => 'login']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }

    public function test_index_can_be_filtered_by_id(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $matching = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['search' => (string) $matching->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }

    public function test_index_can_be_filtered_by_classification(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Unitário']);
        $otherClassification = Classification::create(['name' => 'Integração']);

        $matching = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $otherClassification->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['classification_id' => $classification->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }

    public function test_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $approved = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Aprovado,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Pendente,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['status' => TestCaseStatus::Aprovado->value]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $approved->id)
        );
    }

    public function test_index_can_be_filtered_by_assigned_to_me(): void
    {
        $qa = $this->createUserWithRole('qa');
        $classification = Classification::create(['name' => 'Funcional']);

        $assignedToMe = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'assigned_to' => $qa->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
        ]);

        $response = $this->actingAs($qa)->get(route('test-cases.index', ['assigned_to_me' => '1']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $assignedToMe->id)
        );
    }

    public function test_index_can_combine_multiple_filters(): void
    {
        $qa = $this->createUserWithRole('qa');
        $classification = Classification::create(['name' => 'Funcional']);
        $otherClassification = Classification::create(['name' => 'Integração']);

        $matching = TestCaseModel::create([
            'title' => 'Registrar dados de execução',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'assigned_to' => $qa->id,
            'status' => TestCaseStatus::Pendente,
        ]);
        TestCaseModel::create([
            'title' => 'Registrar dados de execução - outra classificação',
            'classification_id' => $otherClassification->id,
            'created_by' => $qa->id,
            'assigned_to' => $qa->id,
            'status' => TestCaseStatus::Pendente,
        ]);
        TestCaseModel::create([
            'title' => 'Registrar dados de execução - outro status',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'assigned_to' => $qa->id,
            'status' => TestCaseStatus::Aprovado,
        ]);
        TestCaseModel::create([
            'title' => 'Registrar dados de execução - outro responsável',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'status' => TestCaseStatus::Pendente,
        ]);

        $response = $this->actingAs($qa)->get(route('test-cases.index', [
            'search' => 'execução',
            'classification_id' => $classification->id,
            'status' => TestCaseStatus::Pendente->value,
            'assigned_to_me' => '1',
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }

    public function test_index_defaults_new_test_cases_to_pending_status(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('test_cases', [
            'title' => 'Login com credenciais válidas',
            'status' => TestCaseStatus::Pendente->value,
        ]);
    }

    public function test_create_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        Classification::create(['name' => 'Funcional']);

        $response = $this->actingAs($user)->get(route('test-cases.create'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/create')
            ->has('classifications', 1)
        );
    }

    public function test_a_test_case_can_be_created_with_steps(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);
        $template = TestTemplate::create([
            'title' => 'Template padrão',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('test-cases.store'), [
            'title' => 'Login com credenciais válidas',
            'description' => 'Garante que o login funciona com dados corretos.',
            'classification_id' => $classification->id,
            'template_id' => $template->id,
            'steps' => [
                ['description' => 'Acessar a tela de login', 'expected_result' => 'Tela carregada'],
                ['description' => 'Preencher email e senha válidos', 'expected_result' => null],
                ['description' => 'Clicar em entrar', 'expected_result' => 'Usuário autenticado'],
            ],
        ]);

        $this->assertDatabaseHas('test_cases', [
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'template_id' => $template->id,
            'created_by' => $user->id,
        ]);

        $testCase = TestCaseModel::where('title', 'Login com credenciais válidas')->firstOrFail();

        $response->assertRedirect(route('test-cases.show', $testCase));

        $this->assertCount(3, $testCase->steps);
        $this->assertSame(1, $testCase->steps->first()->order);
        $this->assertSame('Acessar a tela de login', $testCase->steps->first()->description);
    }

    public function test_a_test_case_can_be_created_with_a_status(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $this->actingAs($user)->post(route('test-cases.store'), [
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'status' => TestCaseStatus::Regressao->value,
            'steps' => [
                ['description' => 'Acessar a tela de login'],
            ],
        ]);

        $this->assertDatabaseHas('test_cases', [
            'title' => 'Login com credenciais válidas',
            'status' => TestCaseStatus::Regressao->value,
        ]);
    }

    public function test_a_test_case_can_be_viewed(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'description' => 'Garante que o login funciona.',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        $testCase->steps()->create([
            'order' => 1,
            'description' => 'Acessar a tela de login',
            'expected_result' => 'Tela carregada',
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.show', $testCase));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/show')
            ->where('testCase.title', 'Login com credenciais válidas')
            ->where('testCase.classification.name', 'Funcional')
            ->has('testCase.steps', 1)
            ->where('testCase.steps.0.description', 'Acessar a tela de login')
        );
    }

    public function test_show_includes_assignable_users_only_for_qa(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->createUserWithRole('developer');
        $this->createUserWithRole('viewer');
        $developer = $this->createUserWithRole('developer');
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
        ]);

        $this->actingAs($qa)->get(route('test-cases.show', $testCase))
            ->assertInertia(fn (Assert $page) => $page
                ->component('test-cases/show')
                ->has('assignableUsers', 3)
            );

        $this->actingAs($developer)->get(route('test-cases.show', $testCase))
            ->assertInertia(fn (Assert $page) => $page
                ->component('test-cases/show')
                ->has('assignableUsers', 0)
            );
    }

    public function test_creation_fails_without_a_classification(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('test-cases.store'), [
            'title' => 'Login com credenciais válidas',
            'steps' => [
                ['description' => 'Acessar a tela de login'],
            ],
        ]);

        $response->assertSessionHasErrors('classification_id');
        $this->assertDatabaseMissing('test_cases', ['title' => 'Login com credenciais válidas']);
    }

    public function test_creation_fails_without_at_least_one_step(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $response = $this->actingAs($user)->post(route('test-cases.store'), [
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'steps' => [],
        ]);

        $response->assertSessionHasErrors('steps');
        $this->assertDatabaseMissing('test_cases', ['title' => 'Login com credenciais válidas']);
    }

    public function test_edit_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Acessar tela de login']);

        $response = $this->actingAs($user)->get(route('test-cases.edit', $testCase));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/edit')
            ->where('testCase.title', 'Login com credenciais válidas')
            ->has('classifications', 1)
        );
    }

    public function test_a_test_case_can_be_updated_with_steps(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);
        $otherClassification = Classification::create(['name' => 'Regressão']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Passo antigo']);

        $response = $this->actingAs($user)->put(route('test-cases.update', $testCase), [
            'title' => 'Login com credenciais inválidas',
            'description' => 'Garante que o login falha com dados incorretos.',
            'classification_id' => $otherClassification->id,
            'steps' => [
                ['description' => 'Acessar a tela de login', 'expected_result' => 'Tela carregada'],
                ['description' => 'Preencher credenciais inválidas', 'expected_result' => 'Erro exibido'],
            ],
        ]);

        $response->assertRedirect(route('test-cases.show', $testCase));

        $this->assertDatabaseHas('test_cases', [
            'id' => $testCase->id,
            'title' => 'Login com credenciais inválidas',
            'classification_id' => $otherClassification->id,
        ]);

        $testCase->refresh();
        $this->assertCount(2, $testCase->steps);
        $this->assertSame('Acessar a tela de login', $testCase->steps->first()->description);
    }

    public function test_a_test_case_status_can_be_updated(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Pendente,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Passo antigo']);

        $this->actingAs($user)->put(route('test-cases.update', $testCase), [
            'title' => $testCase->title,
            'classification_id' => $classification->id,
            'status' => TestCaseStatus::Reprovado->value,
            'steps' => [
                ['description' => 'Acessar a tela de login'],
            ],
        ]);

        $this->assertDatabaseHas('test_cases', [
            'id' => $testCase->id,
            'status' => TestCaseStatus::Reprovado->value,
        ]);
    }

    public function test_update_fails_without_a_classification(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Passo antigo']);

        $response = $this->actingAs($user)->put(route('test-cases.update', $testCase), [
            'title' => 'Título alterado',
            'steps' => [
                ['description' => 'Acessar a tela de login'],
            ],
        ]);

        $response->assertSessionHasErrors('classification_id');
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'title' => 'Login com credenciais válidas']);
    }

    public function test_qa_can_assign_a_test_case_to_another_qa_user(): void
    {
        $qa = $this->createUserWithRole('qa');
        $otherQa = $this->createUserWithRole('qa');
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
        ]);

        $response = $this->actingAs($qa)->patch(route('test-cases.assign', $testCase), [
            'assigned_to' => $otherQa->id,
        ]);

        $response->assertRedirect(route('test-cases.show', $testCase));
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'assigned_to' => $otherQa->id]);
    }

    public function test_qa_can_assign_a_test_case_to_a_developer(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
        ]);

        $response = $this->actingAs($qa)->patch(route('test-cases.assign', $testCase), [
            'assigned_to' => $developer->id,
        ]);

        $response->assertRedirect(route('test-cases.show', $testCase));
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'assigned_to' => $developer->id]);
    }

    public function test_qa_can_unassign_a_test_case(): void
    {
        $qa = $this->createUserWithRole('qa');
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'assigned_to' => $qa->id,
        ]);

        $this->actingAs($qa)->patch(route('test-cases.assign', $testCase), [
            'assigned_to' => null,
        ]);

        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'assigned_to' => null]);
    }

    public function test_qa_cannot_assign_a_test_case_to_a_viewer(): void
    {
        $qa = $this->createUserWithRole('qa');
        $viewer = $this->createUserWithRole('viewer');
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
        ]);

        $response = $this->actingAs($qa)->patch(route('test-cases.assign', $testCase), [
            'assigned_to' => $viewer->id,
        ]);

        $response->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'assigned_to' => null]);
    }

    public function test_non_qa_users_cannot_assign_test_cases(): void
    {
        $developer = $this->createUserWithRole('developer');
        $otherDeveloper = $this->createUserWithRole('developer');
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $developer->id,
        ]);

        $response = $this->actingAs($developer)->patch(route('test-cases.assign', $testCase), [
            'assigned_to' => $otherDeveloper->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'assigned_to' => null]);
    }

    public function test_status_can_be_updated_in_bulk(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $first = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Pendente,
        ]);
        $second = TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Pendente,
        ]);
        $untouched = TestCaseModel::create([
            'title' => 'Logout',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Pendente,
        ]);

        $response = $this->actingAs($user)->patch(route('test-cases.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'status' => TestCaseStatus::Aprovado->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('test_cases', ['id' => $first->id, 'status' => TestCaseStatus::Aprovado->value]);
        $this->assertDatabaseHas('test_cases', ['id' => $second->id, 'status' => TestCaseStatus::Aprovado->value]);
        $this->assertDatabaseHas('test_cases', ['id' => $untouched->id, 'status' => TestCaseStatus::Pendente->value]);
    }

    public function test_bulk_status_update_fails_without_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('test-cases.bulk-status'), [
            'ids' => [],
            'status' => TestCaseStatus::Aprovado->value,
        ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_status_update_fails_with_invalid_status(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'status' => TestCaseStatus::Pendente,
        ]);

        $response = $this->actingAs($user)->patch(route('test-cases.bulk-status'), [
            'ids' => [$testCase->id],
            'status' => 'NAO_EXISTE',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'status' => TestCaseStatus::Pendente->value]);
    }

    public function test_a_test_case_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $testCase = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Acessar tela de login']);

        $response = $this->actingAs($user)->delete(route('test-cases.delete', $testCase));

        $response->assertRedirect(route('test-cases.index'));
        $this->assertDatabaseMissing('test_cases', ['id' => $testCase->id]);
        $this->assertDatabaseMissing('test_steps', ['test_case_id' => $testCase->id]);
    }
}
