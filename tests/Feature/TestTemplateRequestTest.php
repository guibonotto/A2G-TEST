<?php

namespace Tests\Feature;

use App\Http\Requests\TestTemplates\StoreTestTemplateRequest;
use App\Http\Requests\TestTemplates\UpdateTestTemplateRequest;
use App\Models\Classification;
use App\Models\TestTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TestTemplateRequestTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string>>
     */
    private function storeErrors(array $data): array
    {
        return Validator::make($data, (new StoreTestTemplateRequest)->rules())->errors()->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string>>
     */
    private function updateErrors(array $data, TestTemplate $template): array
    {
        $request = new UpdateTestTemplateRequest;
        $request->setRouteResolver(function () use ($template): Route {
            $route = new Route('PUT', 'management/templates/{testTemplate}', []);
            $route->bind(request());
            $route->setParameter('testTemplate', $template);

            return $route;
        });

        return Validator::make($data, $request->rules())->errors()->toArray();
    }

    public function test_store_accepts_a_valid_payload(): void
    {
        $this->assertSame([], $this->storeErrors($this->validPayload()));
    }

    public function test_store_requires_title_and_at_least_one_step(): void
    {
        $errors = $this->storeErrors(['title' => '', 'steps' => []]);

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('steps', $errors);
    }

    public function test_store_requires_a_description_on_every_step(): void
    {
        $payload = $this->validPayload();
        $payload['steps'][1]['description'] = '';

        $errors = $this->storeErrors($payload);

        $this->assertArrayHasKey('steps.1.description', $errors);
        $this->assertArrayNotHasKey('steps.0.description', $errors);
    }

    public function test_store_rejects_a_duplicate_title(): void
    {
        TestTemplate::factory()->create(['title' => 'Login flow']);

        $this->assertArrayHasKey('title', $this->storeErrors($this->validPayload()));
    }

    public function test_store_rejects_an_unknown_classification(): void
    {
        $payload = [...$this->validPayload(), 'classification_id' => 999];

        $this->assertArrayHasKey('classification_id', $this->storeErrors($payload));
    }

    public function test_store_accepts_an_existing_classification(): void
    {
        $classification = Classification::create(['name' => 'Functional']);
        $payload = [...$this->validPayload(), 'classification_id' => $classification->id];

        $this->assertSame([], $this->storeErrors($payload));
    }

    public function test_update_ignores_the_title_of_the_template_being_edited(): void
    {
        $template = TestTemplate::factory()->create(['title' => 'Login flow']);

        $this->assertSame([], $this->updateErrors($this->validPayload(), $template));
    }

    public function test_update_still_rejects_a_title_used_by_another_template(): void
    {
        TestTemplate::factory()->create(['title' => 'Login flow']);
        $template = TestTemplate::factory()->create(['title' => 'Checkout flow']);

        $this->assertArrayHasKey('title', $this->updateErrors($this->validPayload(), $template));
    }
}
