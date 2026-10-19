import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
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
import { statusColorVar } from '@/lib/status-color';
import { executionStatusLabel } from '@/lib/execution-status';
import { bulkStatus, create, index, show } from '@/routes/test-cases';
import type {
    Classification,
    TestCaseFilters,
    TestCaseListItem,
    TestCaseStatus,
} from '@/types';

type Props = {
    testCases: TestCaseListItem[];
    classifications: Classification[];
    statuses: TestCaseStatus[];
    executionResults: string[];
    filters: TestCaseFilters;
};

export default function TestCaseIndex({
    testCases,
    classifications,
    statuses,
    executionResults,
    filters,
}: Props) {
    const { currentProject } = usePage().props;
    const [search, setSearch] = useState(filters.search);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [pendingStatusId, setPendingStatusId] = useState<string>('');
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [applyingBulkStatus, setApplyingBulkStatus] = useState(false);
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

    const pendingStatusName = statuses.find(
        (status) => String(status.id) === pendingStatusId,
    )?.name;

    function reload(overrides: Record<string, unknown>) {
        setSelectedIds([]);

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

    function toggleOne(id: number, checked: boolean) {
        setSelectedIds((current) =>
            checked
                ? [...current, id]
                : current.filter((selectedId) => selectedId !== id),
        );
    }

    function applyBulkStatus() {
        setApplyingBulkStatus(true);

        router.patch(
            bulkStatus.url(),
            { ids: selectedIds, status_id: Number(pendingStatusId) },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedIds([]);
                    setPendingStatusId('');
                },
                onFinish: () => {
                    setApplyingBulkStatus(false);
                    setConfirmOpen(false);
                },
            },
        );
    }

    const allSelected =
        testCases.length > 0 &&
        testCases.every((testCase) => selectedIds.includes(testCase.id));

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
                                            {status.name}
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

                {selectedIds.length > 0 && (
                    <div className="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/50 px-4 py-2 text-sm">
                        <span>{selectedIds.length} selected</span>

                        <Select
                            value={pendingStatusId}
                            onValueChange={setPendingStatusId}
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Change status to..." />
                            </SelectTrigger>

                            <SelectContent>
                                {statuses.map((status) => (
                                    <SelectItem
                                        key={status.id}
                                        value={String(status.id)}
                                    >
                                        {status.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {/* Separate Apply step: picking an option used to fire
                            the change straight away, so one stray click on the
                            open menu changed every selected case. */}
                        <Button
                            size="sm"
                            disabled={!pendingStatusId || applyingBulkStatus}
                            onClick={() => setConfirmOpen(true)}
                        >
                            Apply
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSelectedIds([])}
                        >
                            Clear selection
                        </Button>
                    </div>
                )}

                <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                    <DialogContent>
                        <DialogTitle>Change status?</DialogTitle>
                        <DialogDescription>
                            {selectedIds.length} test case(s) will be set to
                            &ldquo;{pendingStatusName}&rdquo;.
                        </DialogDescription>

                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="button"
                                onClick={applyBulkStatus}
                                disabled={applyingBulkStatus}
                            >
                                Change status
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

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
                                    <th className="w-10 px-4 py-3">
                                        <Checkbox
                                            checked={allSelected}
                                            onCheckedChange={(checked) =>
                                                testCases.forEach((testCase) =>
                                                    toggleOne(
                                                        testCase.id,
                                                        checked === true,
                                                    ),
                                                )
                                            }
                                            aria-label="Select all"
                                        />
                                    </th>
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
                                        <td className="px-4 py-3">
                                            <Checkbox
                                                checked={selectedIds.includes(
                                                    testCase.id,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggleOne(
                                                        testCase.id,
                                                        checked === true,
                                                    )
                                                }
                                                aria-label={`Select ${testCase.title}`}
                                            />
                                        </td>
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
                                                    {testCase.status.name}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Not executed
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {testCase.latestExecution ? (
                                                <Badge variant={testCase.latestExecution.status === 'APROVADO' ? 'success' : testCase.latestExecution.status === 'REPROVADO' ? 'destructive' : 'warning'}>
                                                    {executionStatusLabel(testCase.latestExecution.status)}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">Not executed</span>
                                            )}
                                        </td>
                                        {showClassification && (
                                            <td className="px-4 py-3">
                                                {testCase.classification
                                                    ?.name ?? '—'}
                                            </td>
                                        )}
                                        <td className="px-4 py-3">
                                            {testCase.assignee?.name ?? (
                                                <span className="text-muted-foreground">
                                                    Unassigned
                                                </span>
                                            )}
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
        </>
    );
}

TestCaseIndex.layout = {
    breadcrumbs: [{ title: 'Test cases', href: index() }],
};
