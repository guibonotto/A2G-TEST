<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Project;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_template_with_ordered_steps(): void
    {
        $template = TestTemplate::factory()->withSteps(3)->create();

        $this->assertCount(3, $template->steps);
        $this->assertSame([1, 2, 3], $template->steps->pluck('order')->all());
        $this->assertDatabaseCount('test_template_steps', 3);
    }

    public function test_steps_are_always_returned_in_order(): void
    {
        $template = TestTemplate::factory()->create();
        $template->steps()->create(['order' => 3, 'description' => 'third']);
        $template->steps()->create(['order' => 1, 'description' => 'first']);
        $template->steps()->create(['order' => 2, 'description' => 'second']);

        $this->assertSame(['first', 'second', 'third'], $template->fresh()->steps->pluck('description')->all());
    }

    public function test_deleting_a_template_removes_its_steps(): void
    {
        $template = TestTemplate::factory()->withSteps(2)->create();

        $template->delete();

        $this->assertDatabaseCount('test_template_steps', 0);
    }

    public function test_deleting_a_template_keeps_test_cases_created_from_it(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $classification = Classification::create(['name' => 'Functional']);
        $template = TestTemplate::factory()->withSteps(2)->create();

        $testCase = TestCaseModel::create([
            'title' => 'Login with valid credentials',
            'classification_id' => $classification->id,
            'template_id' => $template->id,
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);
        $testCase->steps()->create(['order' => 1, 'description' => 'Open the login page']);

        $template->delete();

        $this->assertDatabaseHas('test_cases', ['id' => $testCase->id, 'template_id' => null]);
        $this->assertDatabaseHas('test_steps', ['test_case_id' => $testCase->id, 'description' => 'Open the login page']);
    }

    public function test_template_can_have_a_default_classification(): void
    {
        $classification = Classification::create(['name' => 'Functional']);
        $template = TestTemplate::factory()->create(['classification_id' => $classification->id]);

        $this->assertTrue($template->classification->is($classification));
    }

    public function test_deleting_a_classification_clears_the_template_default(): void
    {
        $classification = Classification::create(['name' => 'Functional']);
        $template = TestTemplate::factory()->create(['classification_id' => $classification->id]);

        $classification->delete();

        $this->assertDatabaseHas('test_templates', ['id' => $template->id, 'classification_id' => null]);
    }
}
