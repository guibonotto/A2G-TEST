<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Evidence;
use App\Models\Execution;
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

    private function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createTestCaseFor(User $user): TestCaseModel
    {
        $classification = Classification::firstOrCreate(['name' => 'Integration']);

        return TestCaseModel::create([
            'title' => 'Login with valid credentials',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
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

    private function createEvidenceForNewUser(): Evidence
    {
        return $this->createExecutionWithEvidence(User::factory()->create());
    }
}
