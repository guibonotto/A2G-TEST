import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/react';
// import { Plus, Trash2 } from 'lucide-react'; // desativado: steps múltiplos fora de uso (testes unitários/integração usam só entrada e saída esperada)
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { testCaseStatusLabel } from '@/lib/test-case-status';
import { index, store } from '@/routes/test-cases';
import type {
    AssignableUser,
    RequirementOption,
    TestCaseStatus,
} from '@/types';

type Props = {
    assignableUsers: AssignableUser[];
    statuses: TestCaseStatus[];
    defaultStatusId: number;
    availableRequirements: RequirementOption[];
};

type StepForm = {
    description: string;
    expected_result: string;
};

const emptyStep: StepForm = { description: '', expected_result: '' };

export default function CreateTestCase({
    assignableUsers,
    statuses,
    defaultStatusId,
    availableRequirements,
}: Props) {
    const { currentProject } = usePage().props;

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        tested_method: '',
        description: '',
        status_id: String(defaultStatusId),
        template_id: '',
        assigned_to: '',
        steps: [{ ...emptyStep }] as StepForm[],
        requirement_ids: [] as number[],
    });

    const [linkRequirementOpen, setLinkRequirementOpen] = useState(false);
    const [selectedRequirementId, setSelectedRequirementId] = useState('');

    const linkedRequirements = data.requirement_ids
        .map((id) =>
            availableRequirements.find((requirement) => requirement.id === id),
        )
        .filter((requirement) => requirement !== undefined);
    const linkableRequirements = availableRequirements.filter(
        (requirement) => !data.requirement_ids.includes(requirement.id),
    );

    function submitLinkRequirement(e: FormEvent) {
        e.preventDefault();

        setData('requirement_ids', [
            ...data.requirement_ids,
            Number(selectedRequirementId),
        ]);
        setSelectedRequirementId('');
        setLinkRequirementOpen(false);
    }

    function unlinkRequirement(requirementId: number) {
        setData(
            'requirement_ids',
            data.requirement_ids.filter((id) => id !== requirementId),
        );
    }

    const stepErrors = errors as Record<string, string | undefined>;

    setLayoutProps({
        breadcrumbs: [
            { title: currentProject?.name ?? 'Project', href: '#' },
            { title: 'Test cases', href: index() },
            { title: 'New', href: '#' },
        ],
    });

    function updateStep(
        stepIndex: number,
        field: keyof StepForm,
        value: string,
    ) {
        const steps = data.steps.map((step, i) =>
            i === stepIndex ? { ...step, [field]: value } : step,
        );
        setData('steps', steps);
    }

    /* desativado: steps múltiplos fora de uso (testes unitários/integração usam só entrada e saída esperada)
    function addStep() {
        setData('steps', [...data.steps, { ...emptyStep }]);
    }

    function removeStep(stepIndex: number) {
        setData(
            'steps',
            data.steps.filter((_, i) => i !== stepIndex),
        );
    }
    */

    function submit(e: FormEvent) {
        e.preventDefault();
        post(store.url());
    }

    return (
        <>
            <Head title="Create test case" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Create test case"
                    description="Define the tested method, input, and expected output."
                />

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="tested_method">
                                    Method/function tested (required)
                                </Label>
                                <Input
                                    id="tested_method"
                                    value={data.tested_method}
                                    onChange={(e) =>
                                        setData('tested_method', e.target.value)
                                    }
                                    placeholder="e.g. UserService.validatePassword()"
                                    required
                                />
                                <InputError message={errors.tested_method} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title (required)</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    placeholder="e.g. Validates a user password"
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description (optional)
                                </Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    placeholder="Optional context about what this test covers"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status_id">Status</Label>
                                <Select
                                    value={data.status_id}
                                    onValueChange={(value) =>
                                        setData('status_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="status_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Draft" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem
                                                key={status.id}
                                                value={String(status.id)}
                                            >
                                                {testCaseStatusLabel(status.name)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status_id} />
                            </div>

                            {assignableUsers.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="assigned_to">
                                        Assigned to (optional)
                                    </Label>
                                    <Select
                                        value={data.assigned_to || 'unassigned'}
                                        onValueChange={(value) =>
                                            setData(
                                                'assigned_to',
                                                value === 'unassigned'
                                                    ? ''
                                                    : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="assigned_to"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Unassigned" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="unassigned">
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
                                    <InputError message={errors.assigned_to} />
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <Heading
                                variant="small"
                                title="Test data"
                                description="Input and expected output for the test case."
                            />

                            <InputError message={errors.steps} />

                            <div className="grid gap-2">
                                <Label htmlFor="step-input">
                                    Input (required)
                                </Label>

                                <Textarea
                                    id="step-input"
                                    value={data.steps[0].description}
                                    onChange={(e) =>
                                        updateStep(
                                            0,
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. A valid email and password"
                                    required
                                />

                                <InputError
                                    message={stepErrors['steps.0.description']}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="step-expected">
                                    Expected output (required)
                                </Label>

                                <Textarea
                                    id="step-expected"
                                    value={data.steps[0].expected_result}
                                    onChange={(e) =>
                                        updateStep(
                                            0,
                                            'expected_result',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. Returns true and allows access"
                                    required
                                />

                                <InputError
                                    message={
                                        stepErrors['steps.0.expected_result']
                                    }
                                />
                            </div>

                            {/* desativado: steps múltiplos fora de uso (testes unitários/integração usam só entrada e saída esperada)
                            <div className="flex items-center justify-between">
                                <Heading
                                    variant="small"
                                    title="Steps"
                                    description="Add at least one step."
                                />

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addStep}
                                >
                                    <Plus /> Add step
                                </Button>
                            </div>

                            <InputError message={errors.steps} />

                            {data.steps.map((step, stepIndex) => (
                                <div
                                    key={stepIndex}
                                    className="flex flex-col gap-3 rounded-lg border p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">
                                            Step {stepIndex + 1}
                                        </span>

                                        {data.steps.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeStep(stepIndex)}
                                            >
                                                <Trash2 />
                                            </Button>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor={`step-description-${stepIndex}`}>
                                            Action
                                        </Label>

                                        <Textarea
                                            id={`step-description-${stepIndex}`}
                                            value={step.description}
                                            onChange={(e) =>
                                                updateStep(
                                                    stepIndex,
                                                    'description',
                                                    e.target.value,
                                                )
                                            }
                                        />

                                        <InputError
                                            message={
                                                stepErrors[
                                                    `steps.${stepIndex}.description`
                                                ]
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor={`step-expected-${stepIndex}`}>
                                            Expected result
                                        </Label>

                                        <Textarea
                                            id={`step-expected-${stepIndex}`}
                                            value={step.expected_result}
                                            onChange={(e) =>
                                                updateStep(
                                                    stepIndex,
                                                    'expected_result',
                                                    e.target.value,
                                                )
                                            }
                                        />

                                        <InputError
                                            message={
                                                stepErrors[
                                                    `steps.${stepIndex}.expected_result`
                                                ]
                                            }
                                        />
                                    </div>
                                </div>
                            ))}
                            */}
                        </CardContent>
                    </Card>

                    {availableRequirements.length > 0 && (
                        <Card className="order-first">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>
                                    Linked requirements (
                                    {linkedRequirements.length})
                                </CardTitle>

                                {linkableRequirements.length > 0 && (
                                    <Dialog
                                        open={linkRequirementOpen}
                                        onOpenChange={setLinkRequirementOpen}
                                    >
                                        <DialogTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                            >
                                                Link requirement
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogTitle>
                                                Link requirement
                                            </DialogTitle>
                                            <DialogDescription>
                                                Select a requirement to link to
                                                this test case.
                                            </DialogDescription>

                                            <div className="flex flex-col gap-4">
                                                <Select
                                                    value={
                                                        selectedRequirementId
                                                    }
                                                    onValueChange={
                                                        setSelectedRequirementId
                                                    }
                                                >
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select a requirement" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {linkableRequirements.map(
                                                            (requirement) => (
                                                                <SelectItem
                                                                    key={
                                                                        requirement.id
                                                                    }
                                                                    value={String(
                                                                        requirement.id,
                                                                    )}
                                                                >
                                                                    {
                                                                        requirement.code
                                                                    }{' '}
                                                                    —{' '}
                                                                    {
                                                                        requirement.title
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
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
                                                        type="button"
                                                        disabled={
                                                            !selectedRequirementId
                                                        }
                                                        onClick={
                                                            submitLinkRequirement
                                                        }
                                                    >
                                                        Link
                                                    </Button>
                                                </DialogFooter>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                )}
                            </CardHeader>

                            <CardContent className="flex flex-col gap-2">
                                <InputError message={errors.requirement_ids} />

                                {linkedRequirements.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No requirements linked.
                                    </p>
                                ) : (
                                    linkedRequirements.map((requirement) => (
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

                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    unlinkRequirement(
                                                        requirement.id,
                                                    )
                                                }
                                            >
                                                Unlink
                                            </Button>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <div className="fixed inset-x-0 bottom-0 z-10 border-t bg-background/95 backdrop-blur">
                        <div className="mx-auto flex w-full max-w-3xl items-center justify-end gap-3 px-4 py-3">
                            <Button type="button" variant="secondary" asChild>
                                <Link href={index()}>Cancel</Link>
                            </Button>

                            <Button type="submit" disabled={processing}>
                                Save test case
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}
