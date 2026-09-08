<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Project::current()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Crie ou entre em um projeto para continuar.'),
            ]);

            return redirect()->route('projects.index');
        }

        return $next($request);
    }
}
