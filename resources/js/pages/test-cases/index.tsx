import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClipboardList, List, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { executionStatusLabel } from '@/lib/execution-status';
import { statusColorVar } from '@/lib/status-color';
import { testCaseStatusLabel } from '@/lib/test-case-status';
import { assign as assign_, create, index, show } from '@/routes/test-cases';
import type {
    Classification,
    TestCaseFilters,
    AssignableUser,
    TestCaseListItem,
    TestCaseStatus,
} from '@/types';

type Props = {
    testCases: TestCaseListItem[];
    classifications: Classification[];
    statuses: TestCaseStatus[];
    executionResults: string[];
    filters: TestCaseFilters;
    /** Empty for members who cannot assign; the cell stays read-only then. */
    assignableUsers: AssignableUser[];
};

const UNASSIGNED = 'unassigned';

/**
 * Assigning from the row: the list is where you notice a case has nobody on
 * it, and opening the case just to pick a name was the only way to fix that.
 */
function AssigneeCell({
    testCase,
    assignableUsers,
}: {
    testCase: TestCaseListItem;
    assignableUsers: AssignableUser[];
}) {
    const [saving, setSaving] = useState(false);

    if (assignableUsers.length === 0) {
        return testCase.assignee ? (
            <span>{testCase.assignee.name}</span>
        ) : (
            <span className="text-muted-foreground">Unassigned</span>
        );
    }

    function assign(value: string) {
        setSaving(true);

        router.patch(
            assign_.url(testCase.id),
            {
                assigned_to: value === UNASSIGNED ? null : Number(value),
            },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setSaving(false),
            },
        );
    }

    return (
        <Select
            value={
                testCase.assignee ? String(testCase.assignee.id) : UNASSIGNED
            }
            onValueChange={assign}
            disabled={saving}
        >
            <SelectTrigger
                size="sm"
                className="w-40"
                aria-label={`Assignee for ${testCase.title}`}
            >
                <SelectValue />
            </SelectTrigger>

            <SelectContent>
                <SelectItem value={UNASSIGNED}>
                    <span className="text-muted-foreground">Unassigned</span>
                </SelectItem>

                {assignableUsers.map((user) => (
                    <SelectItem key={user.id} value={String(user.id)}>
                        {user.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function TestCaseIndex({
    testCases,
    classifications,
    statuses,
    executionResults,
    filters,
    assignableUsers,
}: Props) {
    const { currentProject } = usePage().props;
    const [search, setSearch] = useState(filters.search);
    const [requirementsDialogTestCase, setRequirementsDialogTestCase] =
        useState<TestCaseListItem | null>(null);
    const isFirstRender = useRef(true);

    const hasActiveFilters =
        filters.search !== '' ||
        filters.classification_id !== null ||
        filters.situation_id !== null ||
        filters.execution_result !== null ||
        filters.assigned_to_me;

    /**
     * With an empty list the controls had nothing to control; they only
     * competed with the one action that matters. They stay visible when a
     * filter is active, otherwise there would be no way to clear it.
     */
    const showFilters = testCases.length > 0 || hasActiveFilters;

    /** A single classification makes the column and the filter pure noise. */
    const showClassification = classifications.length > 1;

    /** Counts only make sense against the unfiltered list. */
    const showCounts = !hasActiveFilters;

    const countByStatus = (statusId: number) =>
        testCases.filter((testCase) => testCase.status?.id === statusId).length;

    const countByClassification = (classificationId: number) =>
        testCases.filter(
            (testCase) => testCase.classification?.id === classificationId,
        ).length;

    function reload(overrides: Record<string, unknown>) {
        router.get(
            index.url(),
            {
                search,
                classification_id: filters.classification_id,
                situation_id: filters.situation_id,
                execution_result: filters.execution_result,
                assigned_to_me: filters.assigned_to_me,
                ...overrides,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => reload({}), 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <>
            <Head title="Test cases" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Test cases"
                        description={
                            currentProject
                                ? `Test cases in ${currentProject.name}.`
                                : 'All registered test cases.'
                        }
                    />

                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Create test case
                        </Link>
                    </Button>
                </div>

                {showFilters && (
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by title or ID..."
                            className="sm:max-w-xs"
                        />

                        {showClassification && (
                            <Select
                                value={
                                    filters.classification_id
                                        ? String(filters.classification_id)
                                        : 'all'
                                }
                                onValueChange={(value) =>
                                    reload({
                                        classification_id:
                                            value === 'all'
                                                ? null
                                                : Number(value),
                                    })
                                }
                            >
                                <SelectTrigger className="sm:w-52">
                                    <SelectValue placeholder="Test case type" />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="all">
                                        All types
                                    </SelectItem>

                                    {classifications.map((classification) => (
                                        <SelectItem
                                            key={classification.id}
                                            value={String(classification.id)}
                                        >
                                            {classification.name}
                                            {showCounts &&
                                                ` (${countByClassification(classification.id)})`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        <Select
                            value={
                                filters.situation_id
                                    ? String(filters.situation_id)
                                    : 'all'
                            }
                            onValueChange={(value) =>
                                reload({
                                    situation_id:
                                        value === 'all' ? null : Number(value),
                                })
                            }
                        >
                            <SelectTrigger className="sm:w-56">
                                <SelectValue placeholder="Case situation" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="all">
                                    All situations
                                </SelectItem>

                                {statuses.map((status) => (
                                    <SelectItem
                                        key={status.id}
                                        value={String(status.id)}
                                    >
                                        <span className="flex items-center gap-2">
                                            <span
                                                className="size-2.5 shrink-0 rounded-full"
                                                style={{
                                                    backgroundColor:
                                                        statusColorVar(
                                                            status.color,
                                                        ),
                                                }}
                                                aria-hidden="true"
                                            />
                                            {testCaseStatusLabel(status.name)}
                                            {showCounts &&
                                                ` (${countByStatus(status.id)})`}
                                        </span>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.execution_result ?? 'all'}
                            onValueChange={(value) =>
                                reload({
                                    execution_result:
                                        value === 'all' ? null : value,
                                })
                            }
                        >
                            <SelectTrigger className="sm:w-56">
                                <SelectValue placeholder="Last execution result" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All results</SelectItem>
                                {executionResults.map((result) => (
                                    <SelectItem key={result} value={result}>
                                        {executionStatusLabel(result)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="assigned_to_me"
                                checked={filters.assigned_to_me}
                                onCheckedChange={(checked) =>
                                    reload({ assigned_to_me: checked === true })
                                }
                            />

                            <Label
                                htmlFor="assigned_to_me"
                                className="font-normal"
                            >
                                Assigned to me
                            </Label>
                        </div>
                    </div>
                )}

                {testCases.length === 0 ? (
                    <Card>
                        <div className="flex flex-col items-center gap-3 py-12 text-center">
                            <ClipboardList
                                className="size-8 text-muted-foreground"
                                aria-hidden="true"
                            />

                            {hasActiveFilters ? (
                                <>
                                    <p className="text-sm font-medium">
                                        No test cases match these filters
                                    </p>

                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setSearch('');
                                            reload({
                                                search: '',
                                                classification_id: null,
                                                situation_id: null,
                                                execution_result: null,
                                                assigned_to_me: false,
                                            });
                                        }}
                                    >
                                        Clear filters
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <p className="text-sm font-medium">
                                        No test cases yet
                                    </p>

                                    <p className="max-w-sm text-sm text-muted-foreground">
                                        Create the first test case to start
                                        tracking what this project covers.
                                    </p>

                                    <Button asChild size="sm">
                                        <Link href={create()}>
                                            Create test case
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </Card>
                ) : (
                    <Card className="overflow-hidden py-0">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        ID
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Title
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Case situation
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Last execution result
                                    </th>
                                    {showClassification && (
                                        <th className="px-4 py-3 font-medium">
                                            Type
                                        </th>
                                    )}
                                    <th className="px-4 py-3 font-medium">
                                        Requirements
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Tested method
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Assignee
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Created by
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Created at
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {testCases.map((testCase) => (
                                    <tr
                                        key={testCase.id}
                                        className="border-b last:border-0 hover:bg-muted/50"
                                    >
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                            #{testCase.id}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={show(testCase.id)}
                                                className="font-medium text-primary underline-offset-4 hover:underline"
                                            >
                                                {testCase.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {testCase.status ? (
                                                <Badge
                                                    variant={
                                                        testCase.status.color
                                                    }
                                                >
                                                    {testCaseStatusLabel(
                                                        testCase.status.name,
                                                    )}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Not executed
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {testCase.latest_execution ? (
                                                <Badge
                                                    variant={
                                                        testCase
                                                            .latest_execution
                                                            .status ===
                                                        'APROVADO'
                                                            ? 'success'
                                                            : testCase
                                                                    .latest_execution
                                                                    .status ===
                                                                'REPROVADO'
                                                              ? 'destructive'
                                                              : 'warning'
                                                    }
                                                >
                                                    {executionStatusLabel(
                                                        testCase
                                                            .latest_execution
                                                            .status,
                                                    )}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Not executed
                                                </span>
                                            )}
                                        </td>
                                        {showClassification && (
                                            <td className="px-4 py-3">
                                                {testCase.classification
                                                    ?.name ?? '—'}
                                            </td>
                                        )}
                                        <td className="px-4 py-3">
                                            {testCase.requirements.length >
                                            0 ? (
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant="secondary"
                                                        title={
                                                            testCase
                                                                .requirements[0]
                                                                .title
                                                        }
                                                    >
                                                        {
                                                            testCase
                                                                .requirements[0]
                                                                .code
                                                        }
                                                    </Badge>
                                                    {testCase.requirements
                                                        .length > 1 && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 px-2 text-xs"
                                                            onClick={() =>
                                                                setRequirementsDialogTestCase(
                                                                    testCase,
                                                                )
                                                            }
                                                        >
                                                            <List />
                                                            View all (
                                                            {
                                                                testCase
                                                                    .requirements
                                                                    .length
                                                            }
                                                            )
                                                        </Button>
                                                    )}
                                                </div>
                                            ) : (
                                                <Badge
                                                    variant="warning"
                                                    title="No requirement is linked to this test case."
                                                >
                                                    No requirement
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {testCase.tested_method ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <AssigneeCell
                                                testCase={testCase}
                                                assignableUsers={
                                                    assignableUsers
                                                }
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            {testCase.creator?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {new Date(
                                                testCase.created_at,
                                            ).toLocaleDateString('en-US')}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </Card>
                )}
            </div>
            <Dialog
                open={requirementsDialogTestCase !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRequirementsDialogTestCase(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Linked requirements</DialogTitle>
                        <DialogDescription>
                            Requirements linked to test case "
                            {requirementsDialogTestCase?.title}".
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex max-h-80 flex-col gap-2 overflow-y-auto">
                        {requirementsDialogTestCase?.requirements.map(
                            (requirement) => (
                                <div
                                    key={requirement.id}
                                    className="rounded-md border p-3"
                                >
                                    <div className="font-medium">
                                        {requirement.code}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {requirement.title}
                                    </div>
                                </div>
                            ),
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

TestCaseIndex.layout = {
    breadcrumbs: [{ title: 'Test cases', href: index() }],
};
