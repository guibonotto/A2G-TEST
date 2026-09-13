<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Project;
use App\Models\Role;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * Attach the user to a project and mark it as active, so routes behind
     * the `project` middleware can be reached.
     */
    private function actingAsProjectMember(User $user): Project
    {
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);
        $this->actingAs($user);

        return $project;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'title' => 'Login flow',
            'description' => 'Shared steps for every login scenario.',
            'classification_id' => null,
            'steps' => [
                ['description' => 'Open the login page', 'expected_result' => 'Form is displayed'],
                ['description' => 'Fill in the credentials', 'expected_result' => null],
            ],
        ];
    }

    public function test_guests_cannot_access_template_management(): void
    {
        $template = TestTemplate::factory()->create();

        $this->get(route('test-templates.index'))->assertRedirect(route('login'));
        $this->post(route('test-templates.store'))->assertRedirect(route('login'));
        $this->put(route('test-templates.update', $template))->assertRedirect(route('login'));
        $this->delete(route('test-templates.destroy', $template))->assertRedirect(route('login'));
        $this->get(route('test-templates.show', $template))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_manage_templates(): void
    {
        $developer = $this->createUserWithRole('developer');
        $template = TestTemplate::factory()->create();

        $this->actingAs($developer)->get(route('test-templates.index'))->assertForbidden();
        $this->actingAs($developer)->post(route('test-templates.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($developer)->put(route('test-templates.update', $template), $this->validPayload())->assertForbidden();
        $this->actingAs($developer)->delete(route('test-templates.destroy', $template))->assertForbidden();
    }

    public function test_any_project_member_can_fetch_a_template_to_apply_it(): void
    {
        $developer = $this->createUserWithRole('developer');
        $this->actingAsProjectMember($developer);
        $classification = Classification::create(['name' => 'Functional']);
        $template = TestTemplate::factory()->create([
            'description' => 'Template description',
            'classification_id' => $classification->id,
        ]);
        $template->steps()->create(['order' => 2, 'description' => 'Second', 'expected_result' => null]);
        $template->steps()->create(['order' => 1, 'description' => 'First', 'expected_result' => 'Done']);

        $response = $this->getJson(route('test-templates.show', $template));

        $response->assertOk()->assertExactJson([
            'description' => 'Template description',
            'classification_id' => $classification->id,
            'steps' => [
                ['description' => 'First', 'expected_result' => 'Done'],
                ['description' => 'Second', 'expected_result' => null],
            ],
        ]);
    }

    public function test_fetching_a_template_requires_an_active_project(): void
    {
        $developer = $this->createUserWithRole('developer');
        $template = TestTemplate::factory()->create();

        $this->actingAs($developer)->get(route('test-templates.show', $template))
            ->assertRedirect(route('projects.index'));
    }

    public function test_qa_can_view_the_template_list(): void
    {
        $qa = $this->createUserWithRole('qa');
        $classification = Classification::create(['name' => 'Functional']);
        $template = TestTemplate::factory()->withSteps(2)->create([
            'title' => 'Login flow',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
        ]);
        TestCaseModel::create([
            'title' => 'Login with valid credentials',
            'classification_id' => $classification->id,
            'template_id' => $template->id,
            'created_by' => $qa->id,
            'project_id' => Project::factory()->create()->id,
        ]);

        $response = $this->actingAs($qa)->get(route('test-templates.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/templates/index')
            ->has('templates', 1)
            ->where('templates.0.title', 'Login flow')
            ->where('templates.0.creator.name', $qa->name)
            ->where('templates.0.classification.name', 'Functional')
            ->where('templates.0.test_cases_count', 1)
            ->has('templates.0.steps', 2)
            ->has('classifications', 1)
        );
    }

    public function test_qa_can_create_a_template_with_steps(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->post(route('test-templates.store'), $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('test_templates', ['title' => 'Login flow', 'created_by' => $qa->id]);

        $template = TestTemplate::firstOrFail();
        $this->assertSame([1, 2], $template->steps->pluck('order')->all());
        $this->assertSame('Open the login page', $template->steps[0]->description);
        $this->assertNull($template->steps[1]->expected_result);
    }

    public function test_template_creation_requires_at_least_one_step(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->post(route('test-templates.store'), [
            ...$this->validPayload(),
            'steps' => [],
        ]);

        $response->assertSessionHasErrors('steps');
        $this->assertDatabaseCount('test_templates', 0);
    }

    public function test_template_creation_fails_with_duplicate_title(): void
    {
        $qa = $this->createUserWithRole('qa');
        TestTemplate::factory()->create(['title' => 'Login flow']);

        $response = $this->actingAs($qa)->post(route('test-templates.store'), $this->validPayload());

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('test_templates', 1);
    }

    public function test_qa_can_update_a_template_and_its_steps_are_replaced(): void
    {
        $qa = $this->createUserWithRole('qa');
        $template = TestTemplate::factory()->withSteps(2)->create(['title' => 'Login flow']);

        $response = $this->actingAs($qa)->put(route('test-templates.update', $template), [
            'title' => 'Login flow',
            'description' => 'Updated description',
            'classification_id' => null,
            'steps' => [
                ['description' => 'A', 'expected_result' => null],
                ['description' => 'B', 'expected_result' => null],
                ['description' => 'C', 'expected_result' => null],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('test_templates', ['id' => $template->id, 'description' => 'Updated description']);
        $this->assertDatabaseCount('test_template_steps', 3);
        $this->assertSame(['A', 'B', 'C'], $template->fresh()->steps->pluck('description')->all());
        $this->assertSame([1, 2, 3], $template->fresh()->steps->pluck('order')->all());
    }

    public function test_template_update_rejects_a_title_used_by_another_template(): void
    {
        $qa = $this->createUserWithRole('qa');
        TestTemplate::factory()->create(['title' => 'Login flow']);
        $template = TestTemplate::factory()->withSteps(1)->create(['title' => 'Checkout flow']);

        $response = $this->actingAs($qa)->put(route('test-templates.update', $template), $this->validPayload());

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseHas('test_templates', ['id' => $template->id, 'title' => 'Checkout flow']);
    }

    public function test_deleting_a_template_keeps_test_cases_created_from_it(): void
    {
        $qa = $this->createUserWithRole('qa');
        $classification = Classification::create(['name' => 'Functional']);
        $template = TestTemplate::factory()->withSteps(2)->create();
        $testCase = TestCaseModel::create([
            'title' => 'Login with valid credentials',
            'classification_id' => $classification->id,
            'template_id' => $template->id,
            'created_by' => $qa->id,
            'project_id' => Project::factory()->create()->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Copied from template']);

        $response = $this->actingAs($qa)->delete(route('test-templates.destroy', $template));

        $response->assertRedirect();
        $this->assertDatabaseMissing('test_templates', ['id' => $template->id]);
        $this->assertDatabaseCount('test_template_steps', 0);
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'template_id' => null]);
        $this->assertDatabaseHas('test_steps', ['test_case_id' => $testCase->id, 'description' => 'Copied from template']);
    }
}
