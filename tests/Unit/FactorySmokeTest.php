<?php

namespace Tests\Unit;

use App\Models\Classification;
use App\Models\Evidence;
use App\Models\Execution;
use App\Models\JiraIntegration;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestCaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function factoryProvider(): array
    {
        return [
            'classification' => [Classification::class],
            'evidence' => [Evidence::class],
            'execution' => [Execution::class],
            'jira integration' => [JiraIntegration::class],
            'requirement' => [Requirement::class],
            'role' => [Role::class],
            'test case' => [TestCaseModel::class],
            'test case status' => [TestCaseStatus::class],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('factoryProvider')]
    public function test_the_factory_persists_a_valid_model(string $model): void
    {
        $instance = $model::factory()->create();

        $this->assertTrue($instance->exists);
        $this->assertDatabaseHas($instance->getTable(), [$instance->getKeyName() => $instance->getKey()]);
    }

    public function test_factories_can_be_created_more_than_once(): void
    {
        $this->assertCount(3, Role::factory()->count(3)->create());
        $this->assertCount(3, TestCaseModel::factory()->count(3)->create());
        $this->assertCount(3, Requirement::factory()->count(3)->create());
    }
}
