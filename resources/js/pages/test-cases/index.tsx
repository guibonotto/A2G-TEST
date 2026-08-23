import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    filters: TestCaseFilters;
};

export default function TestCaseIndex({
    testCases,
    classifications,
    statuses,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [applyingBulkStatus, setApplyingBulkStatus] = useState(false);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            setSelectedIds([]);

            router.get(
                index.url(),
                {
                    search,
                    classification_id: filters.classification_id,
                    status_id: filters.status_id,
                    assigned_to_me: filters.assigned_to_me,
                },
                {
                    preserveState: true,
                    replace: true,
                    preserveScroll: true,
                },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function handleClassificationChange(value: string) {
        setSelectedIds([]);

        router.get(
            index.url(),
            {
                search,
                classification_id: value === 'all' ? null : Number(value),
                status_id: filters.status_id,
                assigned_to_me: filters.assigned_to_me,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    }

    function handleStatusChange(value: string) {
        setSelectedIds([]);

        router.get(
            index.url(),
            {
                search,
                classification_id: filters.classification_id,
                status_id: value === 'all' ? null : Number(value),
                assigned_to_me: filters.assigned_to_me,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    }

    function handleAssignedToMeChange(checked: boolean) {
        setSelectedIds([]);

        router.get(
            index.url(),
            {
                search,
                classification_id: filters.classification_id,
                status_id: filters.status_id,
                assigned_to_me: checked,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    }

    function toggleAll(checked: boolean) {
        setSelectedIds(
            checked ? testCases.map((testCase) => testCase.id) : [],
        );
    }

    function toggleOne(id: number, checked: boolean) {
        setSelectedIds((current) =>
            checked
                ? [...current, id]
                : current.filter((selectedId) => selectedId !== id),
        );
    }

    function handleBulkStatusChange(value: string) {
        setApplyingBulkStatus(true);

        router.patch(
            bulkStatus.url(),
            { ids: selectedIds, status_id: Number(value) },
            {
                preserveScroll: true,
                onSuccess: () => setSelectedIds([]),
                onFinish: () => setApplyingBulkStatus(false),
            },
        );
    }

    return (
        <>
            <Head title="Test Cases" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Test Cases"
                        description="All registered test cases."
                    />

                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Create Test Case
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name or ID..."
                        className="sm:max-w-xs"
                    />

                    <Select
                        value={
                            filters.classification_id
                                ? String(filters.classification_id)
                                : 'all'
                        }
                        onValueChange={handleClassificationChange}
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
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.status_id ? String(filters.status_id) : 'all'}
                        onValueChange={handleStatusChange}
                    >
                        <SelectTrigger className="sm:w-52">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value="all">
                                All statuses
                            </SelectItem>

                            {statuses.map((status) => (
                                <SelectItem key={status.id} value={String(status.id)}>
                                    {status.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="assigned_to_me"
                            checked={filters.assigned_to_me}
                            onCheckedChange={(checked) =>
                                handleAssignedToMeChange(checked === true)
                            }
                        />

                        <Label
                            htmlFor="assigned_to_me"
                            className="font-normal"
                        >
                            My assigned tests
                        </Label>
                    </div>
                </div>

                {selectedIds.length > 0 && (
                    <div className="flex items-center gap-3 rounded-lg border bg-muted/50 px-4 py-2 text-sm">
                        <span>
                            {selectedIds.length} selected
                        </span>

                        <Select
                            value=""
                            onValueChange={handleBulkStatusChange}
                            disabled={applyingBulkStatus}
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Change status to..." />
                            </SelectTrigger>

                            <SelectContent>
                                {statuses.map((status) => (
                                    <SelectItem key={status.id} value={String(status.id)}>
                                        {status.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSelectedIds([])}
                        >
                            Clear selection
                        </Button>
                    </div>
                )}

                <Card className="overflow-hidden py-0">
                    {testCases.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            No test cases found.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="w-10 px-4 py-3">
                                        <Checkbox
                                            checked={
                                                testCases.length > 0 &&
                                                selectedIds.length ===
                                                    testCases.length
                                            }
                                            onCheckedChange={(checked) =>
                                                toggleAll(checked === true)
                                            }
                                            aria-label="Select all"
                                        />
                                    </th>
                                    <th className="px-4 py-3 font-medium">Título</th>
                                    <th className="px-4 py-3 font-medium">Classificação</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Responsável</th>
                                    <th className="px-4 py-3 font-medium">Passos</th>
                                    <th className="px-4 py-3 font-medium">Criado por</th>
                                    <th className="px-4 py-3 font-medium">Criado em</th>
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

                                        <td className="px-4 py-3">
                                            <Link
                                                href={show(testCase.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {testCase.title}
                                            </Link>
                                        </td>

                                        <td className="px-4 py-3">
                                            {testCase.classification?.name ??
                                                '—'}
                                        </td>

                                        <td className="px-4 py-3">
                                            {testCase.status ? (
                                                <Badge variant={testCase.status.color}>{testCase.status.name}</Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </td>

                                        <td className="px-4 py-3">
                                            {testCase.assignee?.name ?? '—'}
                                        </td>

                                        <td className="px-4 py-3">
                                            {testCase.steps_count}
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
                    )}
                </Card>
            </div>
        </>
    );
}

TestCaseIndex.layout = {
    breadcrumbs: [{ title: 'Test Cases', href: index() }],
};