<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_deployment_seeder_creates_the_catalogue_and_no_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Role::query()->count());
        $this->assertSame(2, Classification::query()->count());

        /**
         * The demo accounts used to be created here with the password
         * "password", so seeding a live deployment published an administrator
         * anyone could log in as.
         */
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Project::query()->count());
    }

    public function test_the_demo_seeder_is_the_one_that_creates_the_demo_project(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $project = Project::query()->where('name', DemoSeeder::PROJECT)->first();

        $this->assertNotNull($project);
        $this->assertSame(3, User::query()->count());
        $this->assertSame(
            31,
            Requirement::query()->where('project_id', $project->id)->count(),
        );
    }

    public function test_the_demo_accounts_do_not_carry_a_password_taken_from_the_source(): void
    {
        config(['app.demo_password' => null]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $password = User::query()->where('email', 'admin@a2gtest.com')->value('password');

        $this->assertFalse(Hash::check('password', $password));
    }

    public function test_the_demo_accounts_take_the_password_the_environment_sets(): void
    {
        config(['app.demo_password' => 'uma-senha-escolhida']);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $password = User::query()->where('email', 'admin@a2gtest.com')->value('password');

        $this->assertTrue(Hash::check('uma-senha-escolhida', $password));
    }
}
