<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestCaseControllerTest extends TestCase
{
    use RefreshDatabase;

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
