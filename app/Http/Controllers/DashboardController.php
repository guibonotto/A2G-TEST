<?php

namespace App\Http\Controllers;

use App\Models\Classification;
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
        return Inertia::render('dashboard', [
            'stats' => $this->buildStats(),
            'statusBreakdown' => $this->buildStatusBreakdown(),
            'classificationBreakdown' => $this->buildClassificationBreakdown(),
            'workload' => $this->buildWorkload(),
            'creationTrend' => $this->buildCreationTrend(),
        ]);
    }

    /**
     * build the dashboard stats.
     *
     * @return array [total: int, unassigned: int, createdLast7Days: int, statusesInUse: int]
     */
    private function buildStats(): array
    {
        return [
            'total' => TestCase::query()->count(),
            'unassigned' => TestCase::query()->whereNull('assigned_to')->count(),
            'createdLast7Days' => TestCase::query()->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'statusesInUse' => TestCaseStatus::query()->has('testCases')->count(),
        ];
    }

    /**
     * build the dashboard status breakdown.
     *
     * @return array [id: int, name: string, color: string, total: int]
     */
    private function buildStatusBreakdown(): array
    {
        $breakdown = TestCaseStatus::query()
            ->withCount('testCases')
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
        $withoutStatus = TestCase::query()->whereNull('status_id')->count();
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
    private function buildClassificationBreakdown(): array
    {
        return Classification::query()
            ->withCount('testCases')
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
    private function buildWorkload(): array
    {
        return TestCase::query()
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
    private function buildCreationTrend(): array
    {
        $days = 30;
        $since = Carbon::today()->subDays($days - 1);
        $counts = TestCase::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');
        $cumulative = TestCase::query()->where('created_at', '<', $since)->count();
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
