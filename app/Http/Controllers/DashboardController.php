<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\Execution;
use App\Models\Project;
use App\Models\TestCase;
use App\Services\Jira\JiraDefectStatusSync;
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
            'requirementsCount' => $project->requirements()->count(),
            /** Asks Jira for fresh statuses, so it loads after the page instead of holding it up. */
            'openDefects' => Inertia::defer(fn (): array => $this->buildOpenDefects($project)),
        ]);
    }

    /**
     * Bugs reported from failed executions that are still open in Jira,
     * grouped by the requirement their test case covers. This closes the
     * traceability chain requirement → test case → execution → defect.
     * Cases with no linked requirement form a group of their own, so their
     * defects are not hidden.
     *
     * @return array{total: int, groups: list<array{requirement: ?array{id: int, code: string, title: string, url: ?string}, defects: list<array{key: string, status: ?string, url: ?string, testCase: array{id: int, title: string}}>}>}
     */
    private function buildOpenDefects(Project $project): array
    {
        $integration = $project->jiraIntegration;

        $executions = Execution::query()
            ->whereNotNull('jira_defect_key')
            ->where('jira_defect_resolved', false)
            ->whereHas('testCase', fn ($query) => $query->where('project_id', $project->id)->whereNull('deleted_at'))
            ->with(['testCase:id,title', 'testCase.requirements:requirements.id,code,title,jira_synced_at'])
            ->get();

        app(JiraDefectStatusSync::class)->refresh($executions, $integration);

        $openExecutions = $executions->reject(fn (Execution $execution): bool => $execution->jira_defect_resolved);

        $groups = [];

        foreach ($openExecutions as $execution) {
            $defect = [
                'key' => $execution->jira_defect_key,
                'status' => $execution->jira_defect_status,
                'url' => $integration?->issueUrl($execution->jira_defect_key),
                'testCase' => $execution->testCase->only(['id', 'title']),
            ];

            $requirements = $execution->testCase->requirements;

            foreach ($requirements->isEmpty() ? [null] : $requirements as $requirement) {
                $groupKey = $requirement?->id ?? 0;

                $groups[$groupKey] ??= [
                    'requirement' => $requirement === null ? null : [
                        'id' => $requirement->id,
                        'code' => $requirement->code,
                        'title' => $requirement->title,
                        'url' => $integration !== null && $requirement->jira_synced_at !== null
                            ? $integration->issueUrl($requirement->code)
                            : null,
                    ],
                    'defects' => [],
                ];

                $groups[$groupKey]['defects'][] = $defect;
            }
        }

        /** Most affected requirements first; the "no requirement" group goes last. */
        $sortedGroups = collect($groups)
            ->sort(fn (array $a, array $b): int => [$a['requirement'] === null, -count($a['defects']), $a['requirement']['code'] ?? '']
                <=> [$b['requirement'] === null, -count($b['defects']), $b['requirement']['code'] ?? ''])
            ->values()
            ->all();

        return [
            'total' => $openExecutions->pluck('jira_defect_key')->unique()->count(),
            'groups' => $sortedGroups,
        ];
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
            'statusesInUse' => TestCase::query()
                ->where('project_id', $project->id)
                ->whereNotNull('status_id')
                ->distinct('status_id')
                ->count('status_id'),
        ];
    }

    /**
     * build the dashboard status breakdown.
     *
     * @return array [id: int, name: string, color: string, total: int]
     */
    private function buildStatusBreakdown(Project $project): array
    {
        $executionStatuses = [
            'PENDENTE' => [
                'name' => 'Pending',
                'color' => 'warning',
                'category' => 'in_progress',
            ],
            'APROVADO' => [
                'name' => 'Passed',
                'color' => 'success',
                'category' => 'approved',
            ],
            'REPROVADO' => [
                'name' => 'Failed',
                'color' => 'destructive',
                'category' => 'failed',
            ],
            'BLOQUEADO' => [
                'name' => 'Blocked',
                'color' => 'info',
                'category' => 'in_progress',
            ],
        ];

        $counts = TestCase::query()
            ->where('project_id', $project->id)
            ->with('latestExecution')
            ->get()
            ->groupBy(fn (TestCase $testCase): string => $testCase->latestExecution?->status ?? 'NOT_EXECUTED')
            ->map->count();

        $breakdown = collect();
        foreach (['PENDENTE', 'APROVADO', 'REPROVADO', 'BLOQUEADO'] as $status) {
            if (($counts[$status] ?? 0) > 0) {
                $breakdown->push([
                    'id' => $status,
                    'name' => $executionStatuses[$status]['name'],
                    'description' => null,
                    'color' => $executionStatuses[$status]['color'],
                    'category' => $executionStatuses[$status]['category'],
                    'total' => $counts[$status],
                ]);
            }
        }

        if (($counts['NOT_EXECUTED'] ?? 0) > 0) {
            $breakdown->push([
                'id' => 'NOT_EXECUTED',
                'name' => 'Not executed',
                'description' => null,
                'color' => 'secondary',
                'category' => 'in_progress',
                'total' => $counts['NOT_EXECUTED'],
            ]);
        }

        return $breakdown->all();
    }

    /**
     * build the dashboard classification breakdown.
     *
     * Classification is optional, so cases without one get their own
     * "Unclassified" entry instead of disappearing from the chart.
     */
    private function buildClassificationBreakdown(Project $project): array
    {
        $breakdown = Classification::query()
            ->withCount(['testCases' => fn ($query) => $query->where('project_id', $project->id)])
            ->orderByDesc('test_cases_count')
            ->get(['id', 'name'])
            ->map(fn (Classification $classification): array => [
                'id' => $classification->id,
                'name' => $classification->name,
                'total' => $classification->test_cases_count,
            ]);

        $unclassified = TestCase::query()
            ->where('project_id', $project->id)
            ->whereNull('classification_id')
            ->count();

        if ($unclassified > 0) {
            $breakdown->push([
                'id' => 0,
                'name' => 'Unclassified',
                'total' => $unclassified,
            ]);
        }

        return $breakdown->all();
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
