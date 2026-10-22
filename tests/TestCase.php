<?php

namespace Tests;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Create a user holding the given role (seeded with the same permissions as
     * production) in a project. Roles belong to project memberships, so without
     * a project the user joins the one selected in the session, which is
     * created and selected first when there is none.
     */
    protected function createUserWithRole(string $slug, ?Project $project = null): User
    {
        Role::firstOrCreate(['slug' => $slug], [
            'name' => $slug,
            'permissions' => in_array($slug, ['qa', 'admin'], true)
                ? array_column(Permission::cases(), 'value')
                : [],
        ]);

        $user = User::factory()->create();
        ($project ?? $this->sessionProject())->addMember($user, $slug);

        return $user;
    }

    /**
     * The role the user holds in the first project they joined.
     */
    protected function roleOf(User $user): ?Role
    {
        return Role::find($user->projects()->orderBy('project_user.id')->value('project_user.role_id'));
    }

    /**
     * The project selected in the test's session, creating and selecting one when needed.
     */
    protected function sessionProject(): Project
    {
        $project = Project::find(session('current_project_id'));

        if ($project === null) {
            $project = Project::factory()->create();
            $this->withSession(['current_project_id' => $project->id]);
        }

        return $project;
    }
}
