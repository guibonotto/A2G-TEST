<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->load('role'),
            ],
            'currentProject' => fn () => optional(Project::current())->only(['id', 'uuid', 'name']),
            'myProjects' => fn () => $request->user()
                ? $request->user()->projects()
                    ->orderBy('name')
                    ->get(['projects.id', 'projects.uuid', 'projects.name'])
                    ->map(fn (Project $project): array => $project->only(['id', 'uuid', 'name']))
                : [],
            'assignedToMeCount' => function () use ($request): int {
                $project = Project::current();

                return $request->user() && $project
                    ? TestCase::query()->assignedTo($request->user(), $project->id)->awaitingExecution()->count()
                    : 0;
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
