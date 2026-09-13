import { Form, Head, Link, router, setLayoutProps, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import TestCaseController from '@/actions/App/Http/Controllers/TestCaseController';
import EvidenceFilePicker from '@/components/evidence-file-picker';
import Heading from '@/components/heading';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { executionStatusLabel } from '@/lib/execution-status';
import { show as evidenceShow } from '@/routes/evidences';
import { assign, edit, index, show } from '@/routes/test-cases';
import type { AssignableUser, RequirementOption, TestCaseDetail, TestCaseStatus } from '@/types';

type Props = {
    testCase: TestCaseDetail;
    assignableUsers: AssignableUser[];
    statuses: TestCaseStatus[];
    executionStatuses: string[];
    availableRequirements: RequirementOption[];
};

const UNASSIGNED = 'unassigned';
const NO_STATUS = 'none';

type ExecutionFormData = {
    status: string;
    execution_date: string;
    comment: string;
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
    statuses,
    executionStatuses,
    availableRequirements,
}: Props) {
    const { auth } = usePage().props;
    const [selectedAssignee, setSelectedAssignee] = useState(
        testCase.assignee ? String(testCase.assignee.id) : UNASSIGNED,
    );
    const [assigning, setAssigning] = useState(false);
    const [changingStatus, setChangingStatus] = useState(false);
    const [executionStatusFilter, setExecutionStatusFilter] = useState('all');

    function changeStatus(value: string) {
        setChangingStatus(true);

        router.patch(
            TestCaseController.updateStatus.url(testCase),
            { status_id: value === NO_STATUS ? null : Number(value) },
            {
                preserveScroll: true,
                onFinish: () => setChangingStatus(false),
            },
        );
    }

    const executionForm = useForm<ExecutionFormData>({
        status: 'PENDENTE',
        execution_date: localDateTimeNow(),
        comment: '',
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
    const [linkingRequirement, setLinkingRequirement] = useState(false);

    const linkedRequirementIds = new Set(testCase.requirements.map((requirement) => requirement.id));
    const linkableRequirements = availableRequirements.filter((requirement) => !linkedRequirementIds.has(requirement.id));

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

    function unlinkRequirement(requirementId: number) {
        router.delete(TestCaseController.unlinkRequirement.url(testCase), {
            data: { requirement_id: requirementId },
            preserveScroll: true,
        });
    }

    setLayoutProps({
        breadcrumbs: [
            { title: 'Test Cases', href: index() },
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
                            className="w-fit -ml-2"
                        >
                            <Link href={index()}>
                                <ArrowLeft /> Back to test cases
                            </Link>
                        </Button>

                        <Heading
                            title={testCase.title}
                            description={testCase.description ?? undefined}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={edit(testCase.id)}>Edit</Link>
                        </Button>

                        {auth.user.role?.slug === 'qa' && (
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        Assign
                                    </Button>
                                </DialogTrigger>

                                <DialogContent>
                                    <DialogTitle>
                                        Assign test case
                                    </DialogTitle>

                                    <DialogDescription>
                                        Choose who will be responsible for "
                                        {testCase.title}".
                                    </DialogDescription>

                                    <form
                                        onSubmit={submitAssignment}
                                        className="flex flex-col gap-4"
                                    >
                                        <Select
                                            value={selectedAssignee}
                                            onValueChange={setSelectedAssignee}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select an assignee" />
                                            </SelectTrigger>

                                            <SelectContent>
                                                <SelectItem value={UNASSIGNED}>
                                                    Unassigned
                                                </SelectItem>

                                                {assignableUsers.map((user) => (
                                                    <SelectItem
                                                        key={user.id}
                                                        value={String(user.id)}
                                                    >
                                                        {user.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

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

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                >
                                    Delete
                                </Button>
                            </DialogTrigger>

                            <DialogContent>
                                <DialogTitle>
                                    Delete test case?
                                </DialogTitle>

                                <DialogDescription>
                                    This action cannot be undone. The test case
                                    "{testCase.title}" and all of its steps
                                    will be permanently deleted.
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
                                                variant="destructive"
                                                disabled={processing}
                                            >
                                                Delete
                                            </Button>
                                        </DialogFooter>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>

                        {testCase.classification && (
                            <Badge variant="secondary">
                                {testCase.classification.name}
                            </Badge>
                        )}

                        <Select
                            value={testCase.status ? String(testCase.status.id) : NO_STATUS}
                            onValueChange={changeStatus}
                            disabled={changingStatus}
                        >
                            <SelectTrigger size="sm" aria-label="Test case status">
                                <SelectValue placeholder="Set status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NO_STATUS}>
                                    <span className="text-muted-foreground">No status</span>
                                </SelectItem>
                                {statuses.map((status) => (
                                    <SelectItem key={status.id} value={String(status.id)}>
                                        <Badge variant={status.color}>{status.name}</Badge>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Record execution</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitExecution} className="grid gap-4 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="execution_status">Result</Label>
                                <select
                                    id="execution_status"
                                    value={executionForm.data.status}
                                    onChange={(e) => executionForm.setData('status', e.target.value)}
                                    className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                                    required
                                >
                                    <option value="APROVADO">Passed</option>
                                    <option value="REPROVADO">Failed</option>
                                    <option value="BLOQUEADO">Blocked</option>
                                    <option value="PENDENTE">Pending</option>
                                </select>
                                {executionErrors.status && <p className="text-sm text-destructive">{executionErrors.status}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="execution_date">Date and time</Label>
                                <input
                                    id="execution_date"
                                    type="datetime-local"
                                    value={executionForm.data.execution_date}
                                    onChange={(e) => executionForm.setData('execution_date', e.target.value)}
                                    className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                                    required
                                />
                                {executionErrors.execution_date && (
                                    <p className="text-sm text-destructive">{executionErrors.execution_date}</p>
                                )}
                            </div>

                            <div className="grid gap-2 md:row-span-2">
                                <Label htmlFor="execution_comment">Comment</Label>
                                <Textarea
                                    id="execution_comment"
                                    value={executionForm.data.comment}
                                    onChange={(e) => executionForm.setData('comment', e.target.value)}
                                    placeholder="Execution notes"
                                />
                                {executionErrors.comment && <p className="text-sm text-destructive">{executionErrors.comment}</p>}
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="evidences">Evidence (screenshots, logs)</Label>
                                <EvidenceFilePicker
                                    id="evidences"
                                    files={executionForm.data.evidences}
                                    onChange={(files) => executionForm.setData('evidences', files)}
                                    errors={executionErrors}
                                    disabled={executionForm.processing}
                                />
                            </div>

                            <div className="flex items-end">
                                <Button type="submit" disabled={executionForm.processing}>
                                    {executionForm.processing ? 'Recording...' : 'Record execution'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            General Information
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="grid gap-3 text-sm sm:grid-cols-4">
                        <div>
                            <div className="text-muted-foreground">
                                Classification
                            </div>
                            <div>
                                {testCase.classification?.name ?? '—'}
                            </div>
                        </div>

                        <div>
                            <div className="text-muted-foreground">
                                Template
                            </div>
                            <div>
                                {testCase.template?.title ?? '—'}
                            </div>
                        </div>

                        <div>
                            <div className="text-muted-foreground">
                                Created by
                            </div>
                            <div>
                                {testCase.creator?.name ?? '—'}
                            </div>
                        </div>

                        <div>
                            <div className="text-muted-foreground">
                                Assigned to
                            </div>
                            <div>
                                {testCase.assignee?.name ?? '—'}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Linked requirements ({testCase.requirements.length})</CardTitle>

                        {auth.user.role?.slug === 'qa' && linkableRequirements.length > 0 && (
                            <Dialog open={linkRequirementOpen} onOpenChange={setLinkRequirementOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        Link requirement
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>Link requirement</DialogTitle>
                                    <DialogDescription>
                                        Select a requirement to link to &quot;{testCase.title}&quot;.
                                    </DialogDescription>

                                    <form onSubmit={submitLinkRequirement} className="flex flex-col gap-4">
                                        <Select value={selectedRequirementId} onValueChange={setSelectedRequirementId}>
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select a requirement" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {linkableRequirements.map((requirement) => (
                                                    <SelectItem key={requirement.id} value={String(requirement.id)}>
                                                        {requirement.code} — {requirement.title}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button type="button" variant="secondary">
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button type="submit" disabled={!selectedRequirementId || linkingRequirement}>
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
                            <p className="text-sm text-muted-foreground">No requirements linked.</p>
                        ) : (
                            testCase.requirements.map((requirement) => (
                                <div
                                    key={requirement.id}
                                    className="flex items-center justify-between gap-2 rounded-lg border p-3"
                                >
                                    <div className="text-sm">
                                        <span className="font-medium">{requirement.code}</span> — {requirement.title}
                                    </div>

                                    {auth.user.role?.slug === 'qa' && (
                                        <Button variant="ghost" size="sm" onClick={() => unlinkRequirement(requirement.id)}>
                                            Unlink
                                        </Button>
                                    )}
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Execution history ({testCase.executions.length})</CardTitle>
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
                                    <SelectItem value="all">All results</SelectItem>
                                    {executionStatuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {executionStatusLabel(status)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        {testCase.executions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No executions recorded.</p>
                        ) : filteredExecutions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No executions match this filter.</p>
                        ) : (
                            filteredExecutions.map((execution) => (
                                <div key={execution.id} className="flex flex-col gap-2 rounded-lg border p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Badge variant="outline">{executionStatusLabel(execution.status)}</Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(execution.execution_date).toLocaleString('en-US')}
                                        </span>
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        Executed by {execution.executor?.name ?? 'Deleted user'}
                                    </span>
                                    {execution.comment && <p className="text-sm">{execution.comment}</p>}
                                    {execution.evidences.length > 0 && (
                                        <div className="flex flex-wrap gap-3 pt-1">
                                            {execution.evidences.map((evidence) => (
                                                <div key={evidence.id} className="group relative flex flex-col gap-1">
                                                    <a
                                                        href={evidenceShow.url(evidence.id)}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="flex flex-col gap-1"
                                                        title={evidence.file_name}
                                                    >
                                                        {evidence.mime_type.startsWith('image/') ? (
                                                            <img
                                                                src={evidenceShow.url(evidence.id)}
                                                                alt={evidence.file_name}
                                                                className="h-24 w-32 rounded border object-cover transition group-hover:opacity-80"
                                                            />
                                                        ) : (
                                                            <span className="flex h-24 w-32 items-center justify-center rounded border bg-muted text-xs text-muted-foreground">
                                                                {evidence.file_name.split('.').pop()?.toUpperCase()}
                                                            </span>
                                                        )}
                                                        <span className="max-w-32 truncate text-xs text-muted-foreground">{evidence.file_name}</span>
                                                    </a>

                                                    <Dialog>
                                                        <DialogTrigger asChild>
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
                                                            <DialogTitle>Delete evidence?</DialogTitle>
                                                            <DialogDescription>
                                                                &quot;{evidence.file_name}&quot; will be permanently removed from this
                                                                execution. This action cannot be undone.
                                                            </DialogDescription>

                                                            <Form
                                                                {...TestCaseController.destroyEvidence.form(evidence.id)}
                                                                options={{ preserveScroll: true }}
                                                            >
                                                                {({ processing }) => (
                                                                    <DialogFooter className="gap-2">
                                                                        <DialogClose asChild>
                                                                            <Button type="button" variant="secondary">
                                                                                Cancel
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button type="submit" variant="destructive" disabled={processing}>
                                                                            Delete
                                                                        </Button>
                                                                    </DialogFooter>
                                                                )}
                                                            </Form>
                                                        </DialogContent>
                                                    </Dialog>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    <ExecutionEvidenceForm executionId={execution.id} />
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

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
                <Button type="button" variant="outline" size="sm" onClick={() => setOpen(true)}>
                    Add evidence
                </Button>
            </div>
        );
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-3 rounded-md border border-dashed p-3">
            <Label htmlFor={`evidences-${executionId}`}>Add evidence to this execution</Label>
            <EvidenceFilePicker
                id={`evidences-${executionId}`}
                files={form.data.evidences}
                onChange={(files) => form.setData('evidences', files)}
                errors={errors}
                disabled={form.processing}
            />
            <div className="flex gap-2">
                <Button type="button" variant="secondary" size="sm" disabled={form.processing} onClick={cancel}>
                    Cancel
                </Button>
                <Button type="submit" size="sm" disabled={form.processing || form.data.evidences.length === 0}>
                    {form.processing ? 'Uploading...' : 'Upload'}
                </Button>
            </div>
        </form>
    );
}