<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Project;
use App\Models\Role;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestCaseStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    // private function createUserWithRole(string $slug): User
    // {
    //     $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

    //     return User::factory()->create(['role_id' => $role->id]);
    // }

    /**
     * Create a status in the project selected in the session.
     */
    private function createStatus(string $name, string $color = 'secondary'): TestCaseStatus
    {
        return TestCaseStatus::firstOrCreate(
            ['project_id' => $this->sessionProject()->id, 'name' => $name],
            ['color' => $color],
        );
    }

    public function test_a_new_project_starts_with_the_default_statuses(): void
    {
        $project = Project::factory()->create();

        $this->assertSame(
            ['Draft', 'Obsolete', 'Ready'],
            $project->testCaseStatuses()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_the_list_only_shows_the_current_project_statuses(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->createStatus('Passed', 'success');
        TestCaseStatus::create(['project_id' => Project::factory()->create()->id, 'name' => 'Elsewhere', 'color' => 'blue']);

        $this->actingAs($qa)->get(route('test-case-statuses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('statuses', fn ($statuses) => collect($statuses)->pluck('name')->sort()->values()->all() === ['Draft', 'Obsolete', 'Passed', 'Ready'])
            );
    }

    public function test_a_new_status_belongs_to_the_current_project_only(): void
    {
        $qa = $this->createUserWithRole('qa');
        $otherProject = Project::factory()->create();

        $this->actingAs($qa)->post(route('test-case-statuses.store'), ['name' => 'Bloqueado', 'color' => 'blue']);

        $this->assertDatabaseHas('test_case_statuses', ['project_id' => $this->sessionProject()->id, 'name' => 'Bloqueado']);
        $this->assertDatabaseMissing('test_case_statuses', ['project_id' => $otherProject->id, 'name' => 'Bloqueado']);
    }

    public function test_the_same_name_and_color_can_be_used_in_different_projects(): void
    {
        $qa = $this->createUserWithRole('qa');
        TestCaseStatus::create(['project_id' => Project::factory()->create()->id, 'name' => 'Bloqueado', 'color' => 'blue']);

        $this->actingAs($qa)->post(route('test-case-statuses.store'), ['name' => 'Bloqueado', 'color' => 'blue'])
            ->assertSessionHasNoErrors();
    }

    public function test_statuses_of_another_project_cannot_be_changed_or_deleted(): void
    {
        $qa = $this->createUserWithRole('qa');
        $foreign = TestCaseStatus::create(['project_id' => Project::factory()->create()->id, 'name' => 'Elsewhere', 'color' => 'blue']);

        $this->actingAs($qa)->put(route('test-case-statuses.update', $foreign), ['name' => 'Hijacked', 'color' => 'pink'])->assertNotFound();
        $this->actingAs($qa)->delete(route('test-case-statuses.destroy', $foreign))->assertNotFound();
        $this->assertDatabaseHas('test_case_statuses', ['id' => $foreign->id, 'name' => 'Elsewhere']);
    }

    public function test_guests_cannot_access_status_management(): void
    {
        $status = $this->createStatus('Aprovado', 'success');

        $this->get(route('test-case-statuses.index'))->assertRedirect(route('login'));
        $this->post(route('test-case-statuses.store'))->assertRedirect(route('login'));
        $this->put(route('test-case-statuses.update', $status))->assertRedirect(route('login'));
        $this->delete(route('test-case-statuses.destroy', $status))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_status_management(): void
    {
        $developer = $this->createUserWithRole('developer');
        $status = $this->createStatus('Aprovado', 'success');

        $this->actingAs($developer)->get(route('test-case-statuses.index'))->assertForbidden();
        $this->actingAs($developer)->post(route('test-case-statuses.store'))->assertForbidden();
        $this->actingAs($developer)->put(route('test-case-statuses.update', $status))->assertForbidden();
        $this->actingAs($developer)->delete(route('test-case-statuses.destroy', $status))->assertForbidden();
    }

    public function test_qa_can_view_the_status_list(): void
    {
        $qa = $this->createUserWithRole('qa');
        $status = $this->createStatus('Passed', 'success');
        $classification = Classification::create(['name' => 'Funcional']);
        TestCaseModel::create([
            'title' => 'Login',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'status_id' => $status->id,
            'project_id' => $this->sessionProject()->id,
        ]);

        $response = $this->actingAs($qa)->get(route('test-case-statuses.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/statuses/index')
            ->has('statuses', 4)
            ->where('statuses.2.name', 'Passed')
            ->where('statuses.2.test_cases_count', 1)
        );
    }

    public function test_qa_can_create_a_status(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->post(route('test-case-statuses.store'), [
            'name' => 'Bloqueado',
            'color' => 'blue',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('test_case_statuses', ['name' => 'Bloqueado', 'color' => 'blue']);
    }

    public function test_status_creation_fails_with_duplicate_name(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->createStatus('Aprovado', 'success');

        $response = $this->actingAs($qa)->post(route('test-case-statuses.store'), [
            'name' => 'Aprovado',
            'color' => 'blue',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_status_creation_fails_with_invalid_color(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->post(route('test-case-statuses.store'), [
            'name' => 'Bloqueado',
            'color' => 'roxo',
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_qa_can_update_a_status(): void
    {
        $qa = $this->createUserWithRole('qa');
        $status = $this->createStatus('Aprovado', 'success');

        $response = $this->actingAs($qa)->put(route('test-case-statuses.update', $status), [
            'name' => 'Aprovado com ressalvas',
            'color' => 'blue',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('test_case_statuses', [
            'id' => $status->id,
            'name' => 'Aprovado com ressalvas',
            'color' => 'blue',
        ]);
    }

    public function test_status_update_fails_with_a_name_already_used_by_another_status(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->createStatus('Aprovado', 'success');
        $reprovado = $this->createStatus('Reprovado', 'destructive');

        $response = $this->actingAs($qa)->put(route('test-case-statuses.update', $reprovado), [
            'name' => 'Aprovado',
            'color' => 'destructive',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_qa_can_delete_a_status_and_test_cases_become_unassigned(): void
    {
        $qa = $this->createUserWithRole('qa');
        $status = $this->createStatus('Aprovado', 'success');
        $classification = Classification::create(['name' => 'Funcional']);
        $testCase = TestCaseModel::create([
            'title' => 'Login',
            'classification_id' => $classification->id,
            'created_by' => $qa->id,
            'status_id' => $status->id,
            'project_id' => $this->sessionProject()->id,
        ]);

        $response = $this->actingAs($qa)->delete(route('test-case-statuses.destroy', $status));

        $response->assertRedirect();
        $this->assertDatabaseMissing('test_case_statuses', ['id' => $status->id]);
        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'status_id' => null]);
    }
}
