<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestCaseStatus;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with aggrgated test case metrics.
     */
    public function index(): Response
    {
        $project = Project::current();
        abort_unless($project, 404);

        return Inertia::render('dashboard', [
            'stats' => $this->buildStats($project),
            'statusBreakdown' => $this->buildStatusBreakdown($project),
            'classificationBreakdown' => $this->buildClassificationBreakdown($project),
            'workload' => $this->buildWorkload($project),
            'creationTrend' => $this->buildCreationTrend($project),
        ]);
    }

    /**
     * build the dashboard stats.
     *
     * @return array [total: int, unassigned: int, createdLast7Days: int, statusesInUse: int]
     */
    private function buildStats(Project $project): array
    {
        return [
            'total' => TestCase::query()->where('project_id', $project->id)->count(),
            'unassigned' => TestCase::query()->where('project_id', $project->id)->whereNull('assigned_to')->count(),
            'createdLast7Days' => TestCase::query()->where('project_id', $project->id)->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'statusesInUse' => TestCaseStatus::query()->whereHas('testCases', fn ($query) => $query->where('project_id', $project->id))->count(),
        ];
    }

    /**
     * build the dashboard status breakdown.
     *
     * @return array [id: int, name: string, color: string, total: int]
     */
    private function buildStatusBreakdown(Project $project): array
    {
        $breakdown = TestCaseStatus::query()
            ->withCount(['testCases' => fn ($query) => $query->where('project_id', $project->id)])
            ->orderByDesc('test_cases_count')
            ->get(['id', 'name', 'color'])
            ->map(fn ($status) => [
                'id' => $status->id,
                'name' => $status->name,
                'color' => $status->color,
                'total' => $status->test_cases_count,
            ])
            ->filter(fn (array $row): bool => $row['total'] > 0)
            ->values();
        $withoutStatus = TestCase::query()->where('project_id', $project->id)->whereNull('status_id')->count();
        if ($withoutStatus > 0) {
            $breakdown->push([
                'id' => 0,
                'name' => 'Sem status',
                'color' => 'secondary',
                'total' => $withoutStatus,
            ]);
        }

        return $breakdown->all();
    }

    /**
     * build the dashboard classification breakdown.
     */
    private function buildClassificationBreakdown(Project $project): array
    {
        return Classification::query()
            ->withCount(['testCases' => fn ($query) => $query->where('project_id', $project->id)])
            ->orderByDesc('test_cases_count')
            ->get(['id', 'name'])
            ->map(fn (Classification $classification): array => [
                'id' => $classification->id,
                'name' => $classification->name,
                'total' => $classification->test_cases_count,
            ])
            ->all();
    }

    /**
     * build the dashboard workload for the last 10 users with most assigned test cases.
     */
    private function buildWorkload(Project $project): array
    {
        return TestCase::query()
            ->where('test_cases.project_id', $project->id)
            ->leftJoin('users', 'users.id', '=', 'test_cases.assigned_to')
            ->selectRaw("COALESCE(users.name, 'Sem responsável') as name, COUNT(*) as total")
            ->groupBy('name')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => ['name' => $row->name, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * build the dashboard creation trend for the last 30 days.
     *
     * @return array{created: int, cumulative: int, date: string[]}
     */
    private function buildCreationTrend(Project $project): array
    {
        $days = 30;
        $since = Carbon::today()->subDays($days - 1);
        $counts = TestCase::query()
            ->where('project_id', $project->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');
        $cumulative = TestCase::query()->where('project_id', $project->id)->where('created_at', '<', $since)->count();
        $series = [];
        for ($date = $since->copy(); $date->lte(Carbon::today()); $date->addDay()) {
            $key = $date->toDateString();
            $createdThatDay = (int) ($counts[$key] ?? 0);
            $cumulative += $createdThatDay;
            $series[] = [
                'date' => $key,
                'created' => $createdThatDay,
                'cumulative' => $cumulative,
            ];
        }

        return $series;
    }
}
