import { Form, Head, Link, router, setLayoutProps, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import TestCaseController from '@/actions/App/Http/Controllers/TestCaseController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { assign, edit, index, show } from '@/routes/test-cases';
import type { AssignableUser, RequirementOption, TestCaseDetail } from '@/types';

type Props = {
    testCase: TestCaseDetail;
    assignableUsers: AssignableUser[];
    executionStatuses: string[];
    availableRequirements: RequirementOption[];
};

const UNASSIGNED = 'unassigned';

export default function ShowTestCase({ testCase, assignableUsers, executionStatuses, availableRequirements }: Props) {
    const { auth } = usePage().props;
    const [selectedAssignee, setSelectedAssignee] = useState(
        testCase.assignee ? String(testCase.assignee.id) : UNASSIGNED,
    );
    const [assigning, setAssigning] = useState(false);
    const [executionStatusFilter, setExecutionStatusFilter] = useState('all');
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
                        {testCase.status && (
                            <Badge variant={testCase.status.color}>{testCase.status.name}</Badge>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Registrar execução</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={`/test-cases/${testCase.id}/executions`}
                            method="post"
                            options={{ preserveScroll: true }}
                            className="grid gap-4 md:grid-cols-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_status">Resultado</Label>
                                        <select
                                            id="execution_status"
                                            name="status"
                                            defaultValue="PENDENTE"
                                            className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                                            required
                                        >
                                            <option value="APROVADO">Aprovado</option>
                                            <option value="REPROVADO">Reprovado</option>
                                            <option value="BLOQUEADO">Bloqueado</option>
                                            <option value="PENDENTE">Pendente</option>
                                        </select>
                                        {errors.status && <p className="text-sm text-destructive">{errors.status}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="execution_date">Data e hora</Label>
                                        <input
                                            id="execution_date"
                                            name="execution_date"
                                            type="datetime-local"
                                            defaultValue={new Date().toISOString().slice(0, 16)}
                                            className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                                            required
                                        />
                                        {errors.execution_date && <p className="text-sm text-destructive">{errors.execution_date}</p>}
                                    </div>

                                    <div className="grid gap-2 md:row-span-2">
                                        <Label htmlFor="execution_comment">Comentário</Label>
                                        <Textarea id="execution_comment" name="comment" placeholder="Observações da execução" />
                                        {errors.comment && <p className="text-sm text-destructive">{errors.comment}</p>}
                                    </div>

                                    <div className="flex items-end">
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Registrando...' : 'Registrar execução'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
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
                        <CardTitle>Requisitos vinculados ({testCase.requirements.length})</CardTitle>

                        {auth.user.role?.slug === 'qa' && linkableRequirements.length > 0 && (
                            <Dialog open={linkRequirementOpen} onOpenChange={setLinkRequirementOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        Vincular requisito
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>Vincular requisito</DialogTitle>
                                    <DialogDescription>
                                        Selecione um requisito para vincular a &quot;{testCase.title}&quot;.
                                    </DialogDescription>

                                    <form onSubmit={submitLinkRequirement} className="flex flex-col gap-4">
                                        <Select value={selectedRequirementId} onValueChange={setSelectedRequirementId}>
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Selecione um requisito" />
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
                                                    Cancelar
                                                </Button>
                                            </DialogClose>
                                            <Button type="submit" disabled={!selectedRequirementId || linkingRequirement}>
                                                Vincular
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                    </CardHeader>

                    <CardContent className="flex flex-col gap-2">
                        {testCase.requirements.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Nenhum requisito vinculado.</p>
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
                                            Desvincular
                                        </Button>
                                    )}
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Histórico de execuções ({testCase.executions.length})</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {testCase.executions.length > 0 && (
                            <Select
                                value={executionStatusFilter}
                                onValueChange={setExecutionStatusFilter}
                            >
                                <SelectTrigger className="w-full sm:w-64">
                                    <SelectValue placeholder="Filtrar por resultado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos os resultados</SelectItem>
                                    {executionStatuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {status}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        {testCase.executions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Nenhuma execução registrada.</p>
                        ) : filteredExecutions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Nenhuma execução corresponde ao filtro.</p>
                        ) : (
                            filteredExecutions.map((execution) => (
                                <div key={execution.id} className="flex flex-col gap-2 rounded-lg border p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Badge variant="outline">{execution.status}</Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(execution.execution_date).toLocaleString('pt-BR')}
                                        </span>
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        Executado por {execution.executor?.name ?? 'Usuário removido'}
                                    </span>
                                    {execution.comment && <p className="text-sm">{execution.comment}</p>}
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