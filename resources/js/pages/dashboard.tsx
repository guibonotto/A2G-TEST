import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarPlus,
    FileCheck2,
    ListTodo,
    Rocket,
    UserX,
} from 'lucide-react';
import { ClassificationChart } from '@/components/dashboard/classification-chart';
import { CreationTrendChart } from '@/components/dashboard/creation-trend-chart';
import { StatCard } from '@/components/dashboard/stat-card';
import { StatusDistributionChart } from '@/components/dashboard/status-distribution-chart';
import { WorkloadChart } from '@/components/dashboard/workload-chart';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import {
    create as createTestCase,
    index as testCasesIndex,
} from '@/routes/test-cases';
import type { DashboardProps } from '@/types/dashboard';

export default function Dashboard({
    stats,
    statusBreakdown,
    classificationBreakdown,
    workload,
    creationTrend,
}: DashboardProps) {
    const { currentProject } = usePage().props;

    /**
     * The dashboard is a tracking screen; with nothing to track it was four
     * zeroes and four blank panels with no way forward. An empty project
     * gets a starting point instead.
     */
    if (stats.total === 0) {
        return (
            <>
                <Head title="Dashboard" />

                <div className="flex h-full flex-1 flex-col p-4">
                    <Card className="mx-auto w-full max-w-2xl">
                        <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                            <div className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <Rocket className="size-6" aria-hidden="true" />
                            </div>

                            <div>
                                <h2 className="text-xl font-semibold">
                                    Your project is ready
                                </h2>

                                <p className="mt-2 max-w-md text-sm text-muted-foreground">
                                    Start by creating the first test case.
                                    Charts and indicators show up here once
                                    there is something to track.
                                </p>
                            </div>

                            <div className="flex flex-wrap justify-center gap-2">
                                <Button asChild>
                                    <Link href={createTestCase()}>
                                        Create test case
                                    </Link>
                                </Button>

                                <Button asChild variant="outline">
                                    <Link href={projectsIndex()}>
                                        Invite your team
                                    </Link>
                                </Button>

                                <Button asChild variant="ghost">
                                    <Link href={testCasesIndex()}>
                                        View test cases
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {currentProject && (
                    <p className="text-sm text-muted-foreground">
                        Showing data for{' '}
                        <span className="font-medium text-foreground">
                            {currentProject.name}
                        </span>
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total test cases"
                        value={stats.total}
                        icon={FileCheck2}
                    />
                    <StatCard
                        label="Unassigned"
                        value={stats.unassigned}
                        icon={UserX}
                    />
                    <StatCard
                        label="Created (7 days)"
                        value={stats.createdLast7Days}
                        icon={CalendarPlus}
                    />
                    <StatCard
                        label="Statuses in use"
                        value={stats.statusesInUse}
                        icon={ListTodo}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <StatusDistributionChart data={statusBreakdown} />
                    <CreationTrendChart data={creationTrend} />
                    <ClassificationChart data={classificationBreakdown} />
                    <WorkloadChart data={workload} />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
