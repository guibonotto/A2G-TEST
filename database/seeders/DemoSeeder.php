<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The demo project used to walk someone through the tool, with one account per
 * role and the functional requirements from the proposal already loaded.
 *
 * Run it on purpose, never as part of a deployment:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * The accounts used to be created by {@see DatabaseSeeder} with the password
 * "password", which meant every deployment that seeded its database published
 * an administrator anyone could log in as. The password now comes from
 * DEMO_PASSWORD, or is drawn at random and printed once.
 *
 * Model events stay on: projects generate their uuid when created.
 */
class DemoSeeder extends Seeder
{
    public const PROJECT = 'Demo Project';

    /**
     * @var array<int, array{name: string, email: string, role: string}>
     */
    private array $accounts = [
        ['name' => 'Admin Demo', 'email' => 'admin@a2gtest.com', 'role' => 'admin'],
        ['name' => 'QA Demo', 'email' => 'qa@a2gtest.com', 'role' => 'qa'],
        ['name' => 'Viewer Demo', 'email' => 'viewer@a2gtest.com', 'role' => 'viewer'],
    ];

    public function run(): void
    {
        $password = config('app.demo_password') ?: Str::password(16);

        /** Roles are held per project, so the accounts share one project where each has its role. */
        $project = null;
        $created = false;

        foreach ($this->accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ]
            );

            /** An account that already existed kept the password it already had. */
            $created = $created || $user->wasRecentlyCreated;

            $project ??= Project::firstOrCreate([
                'name' => self::PROJECT,
            ], ['owner_id' => $user->id]);

            $project->addMember($user, $account['role']);
        }

        if ($created && ! config('app.demo_password')) {
            $this->command?->warn("Demo account password: {$password}");
            $this->command?->warn('It is stored nowhere. Write it down now, or set DEMO_PASSWORD.');
        }

        /** Statuses are per project; the demo project got the defaults when it was created. */
        $this->call(RequirementSeeder::class);
    }
}
