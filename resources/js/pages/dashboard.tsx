import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarPlus,
    Check,
    FileCheck2,
    ListTodo,
    Rocket,
    UserX,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { ClassificationChart } from '@/components/dashboard/classification-chart';
import { CreationTrendChart } from '@/components/dashboard/creation-trend-chart';
import {
    OpenDefectsCard,
    OpenDefectsCardSkeleton,
} from '@/components/dashboard/open-defects-card';
import { StatCard } from '@/components/dashboard/stat-card';
import { StatusDistributionChart } from '@/components/dashboard/status-distribution-chart';
import { WorkloadChart } from '@/components/dashboard/workload-chart';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import { index as requirementsIndex } from '@/routes/requirements';
import { create as createTestCase } from '@/routes/test-cases';
import type { DashboardProps } from '@/types/dashboard';

export default function Dashboard({
    stats,
    statusBreakdown,
    classificationBreakdown,
    workload,
    creationTrend,
    requirementsCount,
    openDefects,
}: DashboardProps) {
    const { currentProject } = usePage().props;

    /**
     * The dashboard is a tracking screen; with nothing to track it was four
     * zeroes and four blank panels with no way forward. An empty project
     * gets the work in its order instead: requirements first, then the test
     * cases that cover them.
     */
    if (stats.total === 0) {
        const hasRequirements = requirementsCount > 0;

        return (
            <>
                <Head title="Dashboard" />

                <div className="flex h-full flex-1 flex-col p-4">
                    <Card className="mx-auto w-full max-w-2xl">
                        <CardContent className="flex flex-col gap-6 py-10">
                            <div className="flex flex-col items-center gap-4 text-center">
                                <div className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <Rocket
                                        className="size-6"
                                        aria-hidden="true"
                                    />
                                </div>

                                <div>
                                    <h2 className="text-xl font-semibold">
                                        Your project is ready
                                    </h2>

                                    <p className="mt-2 max-w-md text-sm text-muted-foreground">
                                        Follow these steps to get started.
                                        Charts and indicators show up here once
                                        there is something to track.
                                    </p>
                                </div>
                            </div>

                            <ol className="flex flex-col gap-3">
                                <OnboardingStep
                                    number={1}
                                    done={hasRequirements}
                                    title="Register the project's requirements"
                                    description={
                                        hasRequirements
                                            ? `${requirementsCount} requirement(s) registered.`
                                            : 'Import them from Jira or create them manually.'
                                    }
                                    action={
                                        <Button
                                            asChild
                                            size="sm"
                                            variant={
                                                hasRequirements
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                        >
                                            <Link href={requirementsIndex()}>
                                                {hasRequirements
                                                    ? 'View requirements'
                                                    : 'Add requirements'}
                                            </Link>
                                        </Button>
                                    }
                                />

                                <OnboardingStep
                                    number={2}
                                    done={false}
                                    title="Create test cases linked to them"
                                    description="Each test case can cover one or more requirements."
                                    action={
                                        <Button
                                            asChild
                                            size="sm"
                                            variant={
                                                hasRequirements
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            <Link href={createTestCase()}>
                                                Create test case
                                            </Link>
                                        </Button>
                                    }
                                />
                            </ol>

                            <div className="flex justify-center">
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={projectsIndex()}>
                                        Invite your team
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
                    <Deferred
                        data="openDefects"
                        fallback={<OpenDefectsCardSkeleton />}
                    >
                        {openDefects && <OpenDefectsCard data={openDefects} />}
                    </Deferred>
                </div>
            </div>
        </>
    );
}

/**
 * One step of the empty project's checklist: a number until it is done,
 * then a check, so the user sees where they are in the flow.
 */
function OnboardingStep({
    number,
    done,
    title,
    description,
    action,
}: {
    number: number;
    done: boolean;
    title: string;
    description: string;
    action: ReactNode;
}) {
    return (
        <li className="flex items-center gap-4 rounded-lg border p-4">
            <span
                className={
                    done
                        ? 'flex size-8 shrink-0 items-center justify-center rounded-full bg-success/15 text-success'
                        : 'flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground'
                }
            >
                {done ? <Check className="size-4" aria-label="Done" /> : number}
            </span>

            <div className="min-w-0 flex-1">
                <p className="text-sm font-medium">{title}</p>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>

            {action}
        </li>
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
