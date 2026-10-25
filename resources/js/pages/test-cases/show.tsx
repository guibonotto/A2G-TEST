import {
    Form,
    Head,
    Link,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/react';
import { ArrowLeft, MoreHorizontal, Trash2, Unlink } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import TestCaseController from '@/actions/App/Http/Controllers/TestCaseController';
import EvidenceFilePicker from '@/components/evidence-file-picker';
import Heading from '@/components/heading';
import { RequirementPicker } from '@/components/requirement-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { UserPicker } from '@/components/user-picker';
import { executionStatusLabel } from '@/lib/execution-status';
import { testCaseStatusLabel } from '@/lib/test-case-status';
import { show as evidenceShow } from '@/routes/evidences';
import { index as requirementsIndex } from '@/routes/requirements';
import { assign, edit, index, show } from '@/routes/test-cases';
import type {
    AssignableUser,
    RequirementOption,
    TestCaseDetail,
} from '@/types';

type Props = {
    testCase: TestCaseDetail;
    assignableUsers: AssignableUser[];
    executionStatuses: string[];
    availableRequirements: RequirementOption[];
};

const UNASSIGNED = 'unassigned';

type ExecutionFormData = {
    status: string;
    execution_date: string;
    comment: string;
    obtained_output: string;
    evidences: File[];
};

type FieldErrors = Record<string, string | string[] | undefined>;

function localDateTimeNow(): string {
    const now = new Date();
    const offsetInMs = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offsetInMs).toISOString().slice(0, 16);
}

export default function ShowTestCase({
    testCase,
    assignableUsers,
    executionStatuses,
    availableRequirements,
}: Props) {
    const { auth } = usePage().props;
    const [selectedAssignee, setSelectedAssignee] = useState(
        testCase.assignee ? String(testCase.assignee.id) : UNASSIGNED,
    );
    const [assigning, setAssigning] = useState(false);
    const [assignOpen, setAssignOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [executionStatusFilter, setExecutionStatusFilter] = useState('all');
    const [recordingOpen, setRecordingOpen] = useState(false);
    const canRecordExecution = testCase.status?.name !== 'Draft';
    // A case with executions is archived instead of deleted, to keep traceability.
    const willArchive = testCase.executions.length > 0;
    const evidenceCount = testCase.executions.reduce(
        (total, execution) => total + execution.evidences.length,
        0,
    );

    const executionForm = useForm<ExecutionFormData>({
        status: 'PENDENTE',
        execution_date: localDateTimeNow(),
        comment: '',
        obtained_output: '',
        evidences: [],
    });
    const executionErrors = executionForm.errors as FieldErrors;

    function submitExecution(e: FormEvent) {
        e.preventDefault();

        executionForm.post(TestCaseController.storeExecution.url(testCase), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                executionForm.reset();
                executionForm.setData('execution_date', localDateTimeNow());
            },
        });
    }

    const filteredExecutions = testCase.executions.filter(
        (execution) =>
            executionStatusFilter === 'all' ||
            execution.status === executionStatusFilter,
    );

    const [linkRequirementOpen, setLinkRequirementOpen] = useState(false);
    const [selectedRequirementId, setSelectedRequirementId] = useState('');
    const [unlinkRequirementId, setUnlinkRequirementId] = useState<
        number | null
    >(null);
    const [linkingRequirement, setLinkingRequirement] = useState(false);

    const linkedRequirementIds = new Set(
        testCase.requirements.map((requirement) => requirement.id),
    );
    const linkableRequirements = availableRequirements.filter(
        (requirement) => !linkedRequirementIds.has(requirement.id),
    );

    function submitLinkRequirement(e: FormEvent) {
        e.preventDefault();
        setLinkingRequirement(true);

        router.post(
            TestCaseController.linkRequirement.url(testCase),
            { requirement_id: Number(selectedRequirementId) },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedRequirementId('');
                    setLinkRequirementOpen(false);
                },
                onFinish: () => setLinkingRequirement(false),
            },
        );
    }

    function unlinkRequirement() {
        if (unlinkRequirementId === null) {
            return;
        }

        router.delete(TestCaseController.unlinkRequirement.url(testCase), {
            data: { requirement_id: unlinkRequirementId },
            preserveScroll: true,
            onFinish: () => setUnlinkRequirementId(null),
        });
    }

    setLayoutProps({
        breadcrumbs: [
            { title: 'Test cases', href: index() },
            { title: testCase.title, href: show(testCase.id) },
        ],
    });

    function submitAssignment(e: FormEvent) {
        e.preventDefault();
        setAssigning(true);

        router.patch(
            assign.url(testCase),
            {
                assigned_to:
                    selectedAssignee === UNASSIGNED
                        ? null
                        : Number(selectedAssignee),
            },
            {
                preserveScroll: true,
                onSuccess: () => setAssignOpen(false),
                onFinish: () => setAssigning(false),
            },
        );
    }

    return (
        <>
            <Head title={testCase.title} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-2 w-fit"
                        >
                            <Link href={index()}>
                                <ArrowLeft /> Back to test cases
                            </Link>
                        </Button>

                        <Heading
                            title={testCase.title}
                            description={
                                testCase.tested_method
                                    ? `${testCase.tested_method}${testCase.description ? ` - ${testCase.description}` : ''}`
                                    : (testCase.description ?? undefined)
                            }
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={edit(testCase.id)}>Edit</Link>
                        </Button>

                        {auth.user.role?.slug === 'qa' && (
                            <Dialog
                                open={assignOpen}
                                onOpenChange={setAssignOpen}
                            >
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        Assign
                                    </Button>
                                </DialogTrigger>

                                <DialogContent>
                                    <DialogTitle>Assign test case</DialogTitle>

                                    <DialogDescription>
                                        Choose who will be responsible for "
                                        {testCase.title}".
                                    </DialogDescription>

                                    <form
                                        onSubmit={submitAssignment}
                                        className="flex flex-col gap-4"
                                    >
                                        <UserPicker
                                            users={assignableUsers}
                                            value={selectedAssignee}
                                            unassignedValue={UNASSIGNED}
                                            onChange={setSelectedAssignee}
                                        />

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                type="submit"
                                                disabled={assigning}
                                            >
                                                Save
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}

                        {/* Delete sat next to Edit as a filled red button,
                            the loudest control on the screen. */}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="More actions"
                                >
                                    <MoreHorizontal className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onSelect={() => setDeleteOpen(true)}
                                    className="text-destructive focus:text-destructive"
                                >
                                    {willArchive
                                        ? 'Archive test case'
                                        : 'Delete test case'}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                            <DialogContent>
                                <DialogTitle>
                                    {willArchive
                                        ? 'Archive test case?'
                                        : 'Delete test case?'}
                                </DialogTitle>

                                <DialogDescription>
                                    {willArchive ? (
                                        <>
                                            "{testCase.title}" has{' '}
                                            {testCase.executions.length}{' '}
                                            execution(s), {evidenceCount}{' '}
                                            evidence file(s) and{' '}
                                            {testCase.requirements.length}{' '}
                                            requirement link(s). To keep
                                            traceability it will be archived
                                            instead of deleted: it leaves the
                                            lists, but all of this is kept and
                                            can be restored.
                                        </>
                                    ) : (
                                        <>
                                            This action cannot be undone. "
                                            {testCase.title}" and its{' '}
                                            {testCase.requirements.length}{' '}
                                            requirement link(s) will be
                                            permanently deleted.
                                        </>
                                    )}
                                </DialogDescription>

                                <Form
                                    {...TestCaseController.deleteTestCase.form(
                                        testCase,
                                    )}
                                >
                                    {({ processing }) => (
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                type="submit"
                                                variant={
                                                    willArchive
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                                disabled={processing}
                                            >
                                                {willArchive
                                                    ? 'Archive'
                                                    : 'Delete'}
                                            </Button>
                                        </DialogFooter>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>

                        {testCase.classification && (
                            <span className="text-sm text-muted-foreground">
                                Type:{' '}
                                <span className="text-foreground">
                                    {testCase.classification.name}
                                </span>
                            </span>
                        )}

                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                            Case situation:
                            {testCase.status ? (
                                <Badge variant={testCase.status.color}>
                                    {testCaseStatusLabel(testCase.status.name)}
                                </Badge>
                            ) : (
                                <span className="text-foreground">Draft</span>
                            )}
                        </span>

                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                            Last result:
                            {testCase.latest_execution ? (
                                <Badge
                                    variant={
                                        testCase.latest_execution.status ===
                                        'APROVADO'
                                            ? 'success'
                                            : testCase.latest_execution
                                                    .status === 'REPROVADO'
                                              ? 'destructive'
                                              : 'warning'
                                    }
                                >
                                    {executionStatusLabel(
                                        testCase.latest_execution.status,
                                    )}
                                </Badge>
                            ) : (
                                <span className="text-foreground">
                                    Not executed
                                </span>
                            )}
                        </span>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            General Information
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="grid gap-3 text-sm sm:grid-cols-3">
                        {/* Template de caso de teste desativado (funcionalidade fora de uso)
                        <div>
                            <div className="text-muted-foreground">
                                Template
                            </div>
                            <div>
                                {testCase.template?.title ?? '—'}
                            </div>
                        </div>
                        */}

                        <div>
                            <div className="text-muted-foreground">
                                Created by
                            </div>
                            <div>{testCase.creator?.name ?? '—'}</div>
                        </div>

                        <div>
                            <div className="text-muted-foreground">
                                Assigned to
                            </div>
                            <div>
                                {testCase.assignee?.name ??
                                    (auth.user.role?.slug === 'qa' ? (
                                        <button
                                            type="button"
                                            onClick={() => setAssignOpen(true)}
                                            className="font-medium text-primary underline-offset-4 hover:underline"
                                        >
                                            Assign someone
                                        </button>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            Unassigned
                                        </span>
                                    ))}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Test data
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-3">
                        <div>
                            <div className="text-xs text-muted-foreground">
                                Input
                            </div>

                            <p className="text-sm">
                                {testCase.steps[0]?.description ?? '—'}
                            </p>
                        </div>

                        <div>
                            <div className="text-xs text-muted-foreground">
                                Expected output
                            </div>

                            <p className="text-sm">
                                {testCase.steps[0]?.expected_result ?? '—'}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>
                            Linked requirements ({testCase.requirements.length})
                        </CardTitle>

                        {auth.user.role?.slug !== 'viewer' &&
                            linkableRequirements.length > 0 && (
                                <Dialog
                                    open={linkRequirementOpen}
                                    onOpenChange={setLinkRequirementOpen}
                                >
                                    <DialogTrigger asChild>
                                        <Button variant="outline" size="sm">
                                            Link requirement
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>
                                            Link requirement
                                        </DialogTitle>
                                        <DialogDescription>
                                            Search and pick a requirement to
                                            link to &quot;{testCase.title}
                                            &quot;.
                                        </DialogDescription>

                                        <form
                                            onSubmit={submitLinkRequirement}
                                            className="flex flex-col gap-4"
                                        >
                                            <RequirementPicker
                                                requirements={
                                                    linkableRequirements
                                                }
                                                value={selectedRequirementId}
                                                onChange={
                                                    setSelectedRequirementId
                                                }
                                            />

                                            <DialogFooter className="gap-2">
                                                <DialogClose asChild>
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                    >
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    type="submit"
                                                    disabled={
                                                        !selectedRequirementId ||
                                                        linkingRequirement
                                                    }
                                                >
                                                    Link
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            )}
                    </CardHeader>

                    <CardContent className="flex flex-col gap-2">
                        {testCase.requirements.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No requirements linked.
                                {auth.user.role?.slug !== 'viewer' &&
                                    availableRequirements.length === 0 && (
                                        <>
                                            {' '}
                                            This project has none yet:{' '}
                                            <Link
                                                href={requirementsIndex()}
                                                className="font-medium text-primary underline-offset-4 hover:underline"
                                            >
                                                register them first
                                            </Link>
                                            .
                                        </>
                                    )}
                            </p>
                        ) : (
                            testCase.requirements.map((requirement) => (
                                <div
                                    key={requirement.id}
                                    className="flex items-center justify-between gap-2 rounded-lg border p-3"
                                >
                                    <div className="text-sm">
                                        <span className="font-medium">
                                            {requirement.code}
                                        </span>{' '}
                                        — {requirement.title}
                                    </div>

                                    {auth.user.role?.slug !== 'viewer' && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            onClick={() =>
                                                setUnlinkRequirementId(
                                                    requirement.id,
                                                )
                                            }
                                        >
                                            <Unlink className="size-3.5" />
                                            Unlink
                                        </Button>
                                    )}
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Dialog
                    open={unlinkRequirementId !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setUnlinkRequirementId(null);
                        }
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Unlink requirement?</DialogTitle>
                        <DialogDescription>
                            This removes the requirement link from the test
                            case. The requirement itself will not be deleted.
                            This action cannot be undone from this screen.
                        </DialogDescription>
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={unlinkRequirement}
                            >
                                Unlink requirement
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {recordingOpen && canRecordExecution ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Record execution</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={submitExecution}
                                className="grid gap-4 md:grid-cols-3"
                            >
                                <div className="grid gap-2 md:col-span-3 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_input">
                                            Input
                                        </Label>
                                        <Textarea
                                            id="execution_input"
                                            value={
                                                testCase.steps[0]
                                                    ?.description ?? '—'
                                            }
                                            readOnly
                                            className="bg-muted"
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_expected_output">
                                            Expected output
                                        </Label>
                                        <Textarea
                                            id="execution_expected_output"
                                            value={
                                                testCase.steps[0]
                                                    ?.expected_result ?? '—'
                                            }
                                            readOnly
                                            className="bg-muted"
                                        />
                                    </div>
                                </div>

                                {executionForm.data.status === 'REPROVADO' && (
                                    <div className="grid gap-2 md:col-span-3">
                                        <Label htmlFor="obtained_output">
                                            Obtained output
                                        </Label>
                                        <Textarea
                                            id="obtained_output"
                                            value={
                                                executionForm.data
                                                    .obtained_output
                                            }
                                            onChange={(e) =>
                                                executionForm.setData(
                                                    'obtained_output',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Describe the actual output returned by the test."
                                            required
                                        />
                                        {executionErrors.obtained_output && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    executionErrors.obtained_output
                                                }
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="grid gap-4 md:col-span-3 md:grid-cols-3 md:items-start">
                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_status">
                                            Result
                                        </Label>
                                        <Select
                                            value={executionForm.data.status}
                                            onValueChange={(value) => {
                                                executionForm.setData(
                                                    'status',
                                                    value,
                                                );

                                                if (value !== 'REPROVADO') {
                                                    executionForm.setData(
                                                        'obtained_output',
                                                        '',
                                                    );
                                                }
                                            }}
                                        >
                                            <SelectTrigger
                                                id="execution_status"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Select result" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="PENDENTE">
                                                    Pending
                                                </SelectItem>
                                                <SelectItem value="APROVADO">
                                                    Passed
                                                </SelectItem>
                                                <SelectItem value="REPROVADO">
                                                    Failed
                                                </SelectItem>
                                                <SelectItem value="BLOQUEADO">
                                                    Blocked
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {executionErrors.status && (
                                            <p className="text-sm text-destructive">
                                                {executionErrors.status}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_date">
                                            Date and time
                                        </Label>
                                        <Input
                                            id="execution_date"
                                            type="datetime-local"
                                            value={
                                                executionForm.data
                                                    .execution_date
                                            }
                                            onChange={(e) =>
                                                executionForm.setData(
                                                    'execution_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        {executionErrors.execution_date && (
                                            <p className="text-sm text-destructive">
                                                {executionErrors.execution_date}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_comment">
                                            Comment
                                        </Label>
                                        <Textarea
                                            id="execution_comment"
                                            value={executionForm.data.comment}
                                            onChange={(e) =>
                                                executionForm.setData(
                                                    'comment',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Execution notes"
                                        />
                                        {executionErrors.comment && (
                                            <p className="text-sm text-destructive">
                                                {executionErrors.comment}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="evidences">
                                        Evidence (screenshots, logs)
                                    </Label>
                                    <EvidenceFilePicker
                                        id="evidences"
                                        files={executionForm.data.evidences}
                                        onChange={(files) =>
                                            executionForm.setData(
                                                'evidences',
                                                files,
                                            )
                                        }
                                        errors={executionErrors}
                                        disabled={executionForm.processing}
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="submit"
                                        disabled={executionForm.processing}
                                    >
                                        {executionForm.processing
                                            ? 'Recording...'
                                            : 'Record execution'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            className="w-fit"
                            disabled={!canRecordExecution}
                            onClick={() => setRecordingOpen(true)}
                        >
                            Record execution
                        </Button>
                        {!canRecordExecution && (
                            <p className="text-sm text-muted-foreground">
                                This test case must leave Draft before an
                                execution can be recorded.
                            </p>
                        )}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>
                            Execution history ({testCase.executions.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {testCase.executions.length > 0 && (
                            <Select
                                value={executionStatusFilter}
                                onValueChange={setExecutionStatusFilter}
                            >
                                <SelectTrigger className="w-full sm:w-64">
                                    <SelectValue placeholder="Filter by result" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All results
                                    </SelectItem>
                                    {executionStatuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {executionStatusLabel(status)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        {testCase.executions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No executions recorded.
                            </p>
                        ) : filteredExecutions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No executions match this filter.
                            </p>
                        ) : (
                            filteredExecutions.map((execution) => (
                                <div
                                    key={execution.id}
                                    className="flex flex-col gap-2 rounded-lg border p-4"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Badge variant="outline">
                                            {executionStatusLabel(
                                                execution.status,
                                            )}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            Version{' '}
                                            {execution.test_case_version}
                                            {' · '}
                                            {new Date(
                                                execution.execution_date,
                                            ).toLocaleString('en-US')}
                                        </span>
                                    </div>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="w-fit"
                                            >
                                                View data at execution
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogTitle>
                                                Test case data · version{' '}
                                                {execution.test_case_version}
                                            </DialogTitle>
                                            <DialogDescription>
                                                Values recorded when this
                                                execution took place.
                                            </DialogDescription>
                                            {execution.test_case_snapshot ? (
                                                <div className="grid gap-3 text-sm">
                                                    <div>
                                                        <p className="text-muted-foreground">
                                                            Title
                                                        </p>
                                                        <p>
                                                            {
                                                                execution
                                                                    .test_case_snapshot
                                                                    .title
                                                            }
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-muted-foreground">
                                                            Method/function
                                                        </p>
                                                        <p>
                                                            {execution
                                                                .test_case_snapshot
                                                                .tested_method ??
                                                                '—'}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-muted-foreground">
                                                            Description
                                                        </p>
                                                        <p className="whitespace-pre-wrap">
                                                            {execution
                                                                .test_case_snapshot
                                                                .description ??
                                                                '—'}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-muted-foreground">
                                                            Input
                                                        </p>
                                                        <p className="whitespace-pre-wrap">
                                                            {execution
                                                                .test_case_snapshot
                                                                .input ?? '—'}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-muted-foreground">
                                                            Expected output
                                                        </p>
                                                        <p className="whitespace-pre-wrap">
                                                            {execution
                                                                .test_case_snapshot
                                                                .expected_output ??
                                                                '—'}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p>
                                                            <span className="text-muted-foreground">
                                                                Status:{' '}
                                                            </span>
                                                            {execution
                                                                .test_case_snapshot
                                                                .status?.name ??
                                                                '—'}
                                                        </p>
                                                        <p>
                                                            <span className="text-muted-foreground">
                                                                Assigned
                                                                to:{' '}
                                                            </span>
                                                            {execution
                                                                .test_case_snapshot
                                                                .assigned_to
                                                                ?.name ?? '—'}
                                                        </p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No snapshot is available for
                                                    this execution.
                                                </p>
                                            )}
                                        </DialogContent>
                                    </Dialog>
                                    <span className="text-sm text-muted-foreground">
                                        Executed by{' '}
                                        {execution.executor?.name ??
                                            'Deleted user'}
                                    </span>
                                    {execution.comment && (
                                        <p className="text-sm">
                                            {execution.comment}
                                        </p>
                                    )}
                                    {execution.status === 'REPROVADO' &&
                                        execution.obtained_output && (
                                            <div className="rounded-md border border-destructive/30 bg-destructive/5 p-3">
                                                <p className="text-xs font-medium text-destructive">
                                                    Obtained output
                                                </p>
                                                <p className="mt-1 text-sm whitespace-pre-wrap">
                                                    {execution.obtained_output}
                                                </p>
                                            </div>
                                        )}
                                    {execution.evidences.length > 0 && (
                                        <div className="flex flex-wrap gap-3 pt-1">
                                            {execution.evidences.map(
                                                (evidence) => (
                                                    <div
                                                        key={evidence.id}
                                                        className="group relative flex flex-col gap-1"
                                                    >
                                                        <a
                                                            href={evidenceShow.url(
                                                                evidence.id,
                                                            )}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="flex flex-col gap-1"
                                                            title={
                                                                evidence.file_name
                                                            }
                                                        >
                                                            {evidence.mime_type.startsWith(
                                                                'image/',
                                                            ) ? (
                                                                <img
                                                                    src={evidenceShow.url(
                                                                        evidence.id,
                                                                    )}
                                                                    alt={
                                                                        evidence.file_name
                                                                    }
                                                                    className="h-24 w-32 rounded border object-cover transition group-hover:opacity-80"
                                                                />
                                                            ) : (
                                                                <span className="flex h-24 w-32 items-center justify-center rounded border bg-muted text-xs text-muted-foreground">
                                                                    {evidence.file_name
                                                                        .split(
                                                                            '.',
                                                                        )
                                                                        .pop()
                                                                        ?.toUpperCase()}
                                                                </span>
                                                            )}
                                                            <span className="max-w-32 truncate text-xs text-muted-foreground">
                                                                {
                                                                    evidence.file_name
                                                                }
                                                            </span>
                                                        </a>

                                                        <Dialog>
                                                            <DialogTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    variant="destructive"
                                                                    size="icon"
                                                                    className="absolute top-1 right-1 size-7 opacity-0 transition group-hover:opacity-100 focus-visible:opacity-100"
                                                                    aria-label={`Delete ${evidence.file_name}`}
                                                                >
                                                                    <Trash2 className="size-3.5" />
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent>
                                                                <DialogTitle>
                                                                    Delete
                                                                    evidence?
                                                                </DialogTitle>
                                                                <DialogDescription>
                                                                    &quot;
                                                                    {
                                                                        evidence.file_name
                                                                    }
                                                                    &quot; will
                                                                    be
                                                                    permanently
                                                                    removed from
                                                                    this
                                                                    execution.
                                                                    This action
                                                                    cannot be
                                                                    undone.
                                                                </DialogDescription>

                                                                <Form
                                                                    {...TestCaseController.destroyEvidence.form(
                                                                        evidence.id,
                                                                    )}
                                                                    options={{
                                                                        preserveScroll: true,
                                                                    }}
                                                                >
                                                                    {({
                                                                        processing,
                                                                    }) => (
                                                                        <DialogFooter className="gap-2">
                                                                            <DialogClose
                                                                                asChild
                                                                            >
                                                                                <Button
                                                                                    type="button"
                                                                                    variant="secondary"
                                                                                >
                                                                                    Cancel
                                                                                </Button>
                                                                            </DialogClose>
                                                                            <Button
                                                                                type="submit"
                                                                                variant="destructive"
                                                                                disabled={
                                                                                    processing
                                                                                }
                                                                            >
                                                                                Delete
                                                                            </Button>
                                                                        </DialogFooter>
                                                                    )}
                                                                </Form>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    )}

                                    <ExecutionEvidenceForm
                                        executionId={execution.id}
                                    />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                {/* desativado: steps múltiplos fora de uso (testes unitários/integração usam só entrada e saída esperada)
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Steps ({testCase.steps.length})
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-3">
                        {testCase.steps.map((step) => (
                            <div
                                key={step.id}
                                className="flex flex-col gap-2 rounded-lg border p-4"
                            >
                                <span className="text-sm font-medium">
                                    Step {step.order}
                                </span>

                                <div>
                                    <div className="text-xs text-muted-foreground">
                                        Action
                                    </div>

                                    <p className="text-sm">
                                        {step.description}
                                    </p>
                                </div>

                                {step.expected_result && (
                                    <div>
                                        <div className="text-xs text-muted-foreground">
                                            Expected Result
                                        </div>

                                        <p className="text-sm">
                                            {step.expected_result}
                                        </p>
                                    </div>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
                */}
            </div>
        </>
    );
}

/**
 * Lets the user attach more evidence files to an execution that was already recorded.
 */
function ExecutionEvidenceForm({ executionId }: { executionId: number }) {
    const [open, setOpen] = useState(false);
    const form = useForm<{ evidences: File[] }>({ evidences: [] });
    const errors = form.errors as FieldErrors;

    function submit(e: FormEvent) {
        e.preventDefault();

        form.post(TestCaseController.storeEvidence.url(executionId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    }

    function cancel() {
        form.reset();
        form.clearErrors();
        setOpen(false);
    }

    if (!open) {
        return (
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setOpen(true)}
                >
                    Add evidence
                </Button>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-3 rounded-md border border-dashed p-3"
        >
            <Label htmlFor={`evidences-${executionId}`}>
                Add evidence to this execution
            </Label>
            <EvidenceFilePicker
                id={`evidences-${executionId}`}
                files={form.data.evidences}
                onChange={(files) => form.setData('evidences', files)}
                errors={errors}
                disabled={form.processing}
            />
            <div className="flex gap-2">
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    disabled={form.processing}
                    onClick={cancel}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    size="sm"
                    disabled={
                        form.processing || form.data.evidences.length === 0
                    }
                >
                    {form.processing ? 'Uploading...' : 'Upload'}
                </Button>
            </div>
        </form>
    );
}
