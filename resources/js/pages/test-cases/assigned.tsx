import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2, PlayCircle } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { executionStatusLabel } from '@/lib/execution-status';
import { testCaseStatusLabel } from '@/lib/test-case-status';
import { assigned, show } from '@/routes/test-cases';
import type { TestCaseListItem } from '@/types';

type AssignedTestCase = Pick<
    TestCaseListItem,
    'id' | 'title' | 'tested_method' | 'status' | 'latest_execution'
>;

type Props = {
    toRun: AssignedTestCase[];
    passed: AssignedTestCase[];
};

function resultVariant(status: string): 'success' | 'destructive' | 'warning' {
    if (status === 'APROVADO') {
        return 'success';
    }

    return status === 'REPROVADO' ? 'destructive' : 'warning';
}

function AssignedRow({ testCase }: { testCase: AssignedTestCase }) {
    const execution = testCase.latest_execution;

    return (
        <li className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <Link
                    href={show(testCase.id)}
                    className="font-medium text-primary underline-offset-4 hover:underline"
                >
                    {testCase.title}
                </Link>
                {testCase.tested_method && (
                    <p className="truncate font-mono text-xs text-muted-foreground">
                        {testCase.tested_method}
                    </p>
                )}
            </div>

            <div className="flex shrink-0 flex-wrap items-center gap-2 text-sm">
                {testCase.status && (
                    <Badge variant={testCase.status.color}>
                        {testCaseStatusLabel(testCase.status.name)}
                    </Badge>
                )}
                {execution ? (
                    <>
                        <Badge variant={resultVariant(execution.status)}>
                            {executionStatusLabel(execution.status)}
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                            {new Date(
                                execution.execution_date,
                            ).toLocaleDateString('en-US')}
                        </span>
                    </>
                ) : (
                    <Badge variant="outline">Not executed</Badge>
                )}
            </div>
        </li>
    );
}

/**
 * Where an assignee finds their work. Before this page the only way was a
 * filter on the test case list, so people did not know a case had been
 * handed to them.
 */
export default function AssignedToMe({ toRun, passed }: Props) {
    const { currentProject } = usePage().props;

    return (
        <>
            <Head title="Assigned to me" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Assigned to me"
                    description={`Test cases in ${currentProject?.name ?? 'this project'} that you are responsible for.`}
                />

                <section className="flex flex-col gap-3">
                    <h2 className="flex items-center gap-2 text-sm font-semibold">
                        <PlayCircle className="size-4 text-primary" />
                        To run ({toRun.length})
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Never executed, failed or blocked. Open a case and
                        record its execution.
                    </p>

                    <Card className="py-0">
                        {toRun.length === 0 ? (
                            <p className="p-6 text-sm text-muted-foreground">
                                Nothing waiting for you. Cases assigned to you
                                will show up here.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {toRun.map((testCase) => (
                                    <AssignedRow
                                        key={testCase.id}
                                        testCase={testCase}
                                    />
                                ))}
                            </ul>
                        )}
                    </Card>
                </section>

                {passed.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <h2 className="flex items-center gap-2 text-sm font-semibold">
                            <CheckCircle2 className="size-4 text-success" />
                            Passed ({passed.length})
                        </h2>

                        <Card className="py-0">
                            <ul className="divide-y">
                                {passed.map((testCase) => (
                                    <AssignedRow
                                        key={testCase.id}
                                        testCase={testCase}
                                    />
                                ))}
                            </ul>
                        </Card>
                    </section>
                )}
            </div>
        </>
    );
}

AssignedToMe.layout = {
    breadcrumbs: [{ title: 'Assigned to me', href: assigned() }],
};
