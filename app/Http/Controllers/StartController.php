<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartController extends Controller
{
    /**
     * Open the screen where work on the current project picks up. The
     * dashboard tracks progress, so it only makes sense once there are test
     * cases; an empty project starts at its first step instead: requirements
     * for whoever can register them, test cases for everyone else.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $project = Project::current();

        if ($project->testCases()->exists()) {
            return to_route('dashboard');
        }

        if ($request->user()->canManageAccess() && $project->requirements()->doesntExist()) {
            return to_route('requirements.index');
        }

        return to_route('test-cases.index');
    }
}
