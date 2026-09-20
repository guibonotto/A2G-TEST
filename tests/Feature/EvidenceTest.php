<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Evidence;
use App\Models\Execution;
use App\Models\Project;
use App\Models\Role;
use App\Models\TestCase as TestCaseModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    // private function createUserWithRole(string $slug): User
    // {
    //     $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

    //     return User::factory()->create(['role_id' => $role->id]);
    // }

    /**
     * Create a project the user belongs to, mark it as the active project in
     * session, and create a test case inside it.
     */
    private function createTestCaseFor(User $user): TestCaseModel
    {
        $classification = Classification::firstOrCreate(['name' => 'Integration']);

        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);

        return TestCaseModel::create([
            'title' => 'Login with valid credentials',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    private function createExecutionWithEvidence(User $user): Evidence
    {
        $testCase = $this->createTestCaseFor($user);

        $execution = $testCase->executions()->create([
            'executed_by' => $user->id,
            'status' => 'APROVADO',
            'execution_date' => now(),
        ]);

        Storage::disk('local')->put('evidences/1/screenshot.png', 'fake-image-content');

        return $execution->evidences()->create([
            'file_name' => 'screenshot.png',
            'file_path' => 'evidences/1/screenshot.png',
            'mime_type' => 'image/png',
            'size' => 18,
            'uploaded_at' => now(),
        ]);
    }

    public function test_user_can_attach_evidence_when_recording_an_execution(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $testCase = $this->createTestCaseFor($qa);

        $response = $this->actingAs($qa)->post(route('test-cases.executions.store', $testCase), [
            'status' => 'APROVADO',
            'execution_date' => now()->toDateTimeString(),
            'evidences' => [UploadedFile::fake()->create('print.png', 64, 'image/png')],
        ]);

        $response->assertRedirect(route('test-cases.show', $testCase));
        $this->assertDatabaseHas('evidences', [
            'file_name' => 'print.png',
            'mime_type' => 'image/png',
        ]);

        $evidence = Evidence::firstOrFail();
        $this->assertSame($testCase->executions()->firstOrFail()->id, $evidence->execution_id);
        Storage::disk('local')->assertExists($evidence->file_path);
    }

    public function test_user_can_attach_multiple_evidences_to_the_same_execution(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $testCase = $this->createTestCaseFor($qa);

        $response = $this->actingAs($qa)->post(route('test-cases.executions.store', $testCase), [
            'status' => 'REPROVADO',
            'execution_date' => now()->toDateTimeString(),
            'evidences' => [
                UploadedFile::fake()->create('screenshot-1.png', 64, 'image/png'),
                UploadedFile::fake()->create('screenshot-2.png', 64, 'image/png'),
                UploadedFile::fake()->create('server.log', 8, 'text/plain'),
            ],
        ]);

        $response->assertRedirect(route('test-cases.show', $testCase));

        $execution = $testCase->executions()->firstOrFail();
        $this->assertCount(3, $execution->evidences);
        $this->assertEqualsCanonicalizing(
            ['screenshot-1.png', 'screenshot-2.png', 'server.log'],
            $execution->evidences->pluck('file_name')->all(),
        );
        $execution->evidences->each(
            fn (Evidence $evidence) => Storage::disk('local')->assertExists($evidence->file_path)
        );
    }

    public function test_execution_rejects_more_than_ten_evidences(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $testCase = $this->createTestCaseFor($qa);

        $response = $this->actingAs($qa)->post(route('test-cases.executions.store', $testCase), [
            'status' => 'APROVADO',
            'execution_date' => now()->toDateTimeString(),
            'evidences' => array_map(
                fn (int $index) => UploadedFile::fake()->create("print-{$index}.png", 8, 'image/png'),
                range(1, 11),
            ),
        ]);

        $response->assertSessionHasErrors('evidences');
        $this->assertDatabaseCount('executions', 0);
        $this->assertDatabaseCount('evidences', 0);
    }

    public function test_execution_can_be_recorded_without_any_evidence(): void
    {
        $qa = $this->createUserWithRole('qa');
        $testCase = $this->createTestCaseFor($qa);

        $response = $this->actingAs($qa)->post(route('test-cases.executions.store', $testCase), [
            'status' => 'REPROVADO',
            'execution_date' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('test-cases.show', $testCase));
        $this->assertDatabaseCount('executions', 1);
        $this->assertDatabaseCount('evidences', 0);
    }

    public function test_evidence_upload_rejects_disallowed_file_types(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $testCase = $this->createTestCaseFor($qa);

        $response = $this->actingAs($qa)->post(route('test-cases.executions.store', $testCase), [
            'status' => 'APROVADO',
            'execution_date' => now()->toDateTimeString(),
            'evidences' => [UploadedFile::fake()->create('malware.php', 10, 'application/x-httpd-php')],
        ]);

        $response->assertSessionHasErrors('evidences.0');
        $this->assertDatabaseCount('evidences', 0);
    }

    public function test_guests_cannot_download_evidence(): void
    {
        Storage::fake('local');

        $evidence = $this->createEvidenceForNewUser();

        $this->get(route('evidences.show', $evidence))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_download_evidence(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $evidence = $this->createExecutionWithEvidence($qa);

        $response = $this->actingAs($qa)->get(route('evidences.show', $evidence));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('content-type'));
    }

    public function test_deleting_an_evidence_also_removes_the_file(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $evidence = $this->createExecutionWithEvidence($qa);
        $path = $evidence->file_path;

        Storage::disk('local')->assertExists($path);

        $response = $this->actingAs($qa)->delete(route('evidences.destroy', $evidence));

        $response->assertRedirect();
        $this->assertDatabaseCount('evidences', 0);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_an_execution_removes_its_evidence_files(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $evidence = $this->createExecutionWithEvidence($qa);
        $path = $evidence->file_path;

        Execution::findOrFail($evidence->execution_id)->delete();

        $this->assertDatabaseCount('evidences', 0);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_user_can_attach_more_evidence_to_an_existing_execution(): void
    {
        Storage::fake('local');

        $qa = $this->createUserWithRole('qa');
        $execution = $this->createExecutionWithEvidence($qa)->execution;

        $response = $this->actingAs($qa)->post(route('executions.evidences.store', $execution), [
            'evidences' => [
                UploadedFile::fake()->create('retry.png', 64, 'image/png'),
                UploadedFile::fake()->create('console.log', 8, 'text/plain'),
            ],
        ]);

        $response->assertRedirect(route('test-cases.show', $execution->testCase));
        $this->assertCount(3, $execution->fresh()->evidences);
        $this->assertDatabaseHas('evidences', ['execution_id' => $execution->id, 'file_name' => 'retry.png']);
        $this->assertDatabaseHas('evidences', ['execution_id' => $execution->id, 'file_name' => 'console.log']);
    }

    public function test_attaching_evidence_requires_at_least_one_file(): void
    {
        $qa = $this->createUserWithRole('qa');
        $execution = $this->createExecutionWithEvidence($qa)->execution;

        $response = $this->actingAs($qa)->post(route('executions.evidences.store', $execution), []);

        $response->assertSessionHasErrors('evidences');
        $this->assertDatabaseCount('evidences', 1);
    }

    public function test_users_outside_the_project_cannot_attach_evidence(): void
    {
        Storage::fake('local');

        $execution = $this->createEvidenceForNewUser()->execution;
        $this->actingAsMemberOfAnotherProject();

        $response = $this->post(route('executions.evidences.store', $execution), [
            'evidences' => [UploadedFile::fake()->create('intruder.png', 8, 'image/png')],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('evidences', 1);
    }

    public function test_users_outside_the_project_cannot_download_evidence(): void
    {
        $evidence = $this->createEvidenceForNewUser();
        $this->actingAsMemberOfAnotherProject();

        $this->get(route('evidences.show', $evidence))->assertForbidden();
    }

    public function test_users_outside_the_project_cannot_delete_evidence(): void
    {
        $evidence = $this->createEvidenceForNewUser();
        $this->actingAsMemberOfAnotherProject();

        $this->delete(route('evidences.destroy', $evidence))->assertForbidden();
        $this->assertDatabaseCount('evidences', 1);
        Storage::disk('local')->assertExists($evidence->file_path);
    }

    private function createEvidenceForNewUser(): Evidence
    {
        return $this->createExecutionWithEvidence(User::factory()->create());
    }

    /**
     * Authenticate as a user whose active project is not the one the
     * evidence under test belongs to.
     */
    private function actingAsMemberOfAnotherProject(): User
    {
        $outsider = $this->createUserWithRole('qa');
        $otherProject = Project::factory()->create();
        $otherProject->members()->attach($outsider);

        $this->withSession(['current_project_id' => $otherProject->id]);
        $this->actingAs($outsider);

        return $outsider;
    }
}
