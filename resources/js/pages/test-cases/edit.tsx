import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
// import { Plus, Trash2 } from 'lucide-react'; // desativado: steps múltiplos fora de uso (testes unitários/integração usam só entrada e saída esperada)
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { index, show, update } from '@/routes/test-cases';
import type { AssignableUser, TestCaseDetail, TestCaseStatus } from '@/types';

type Props = {
    testCase: TestCaseDetail;
    /** Shown read-only: linking happens on the detail screen. */
    linkedRequirements: { id: number; code: string; title: string }[];
    hasExecutions: boolean;
    version: number;
    assignableUsers: AssignableUser[];
    statuses: TestCaseStatus[];
};

type StepForm = {
    description: string;
    expected_result: string;
};

type EditFormData = {
    title: string;
    tested_method: string;
    description: string;
    status_id: string;
    assigned_to: string;
    steps: StepForm[];
};

const emptyStep: StepForm = { description: '', expected_result: '' };

export default function EditTestCase({
    testCase,
    linkedRequirements,
    hasExecutions,
    version,
    assignableUsers,
    statuses,
}: Props) {
    const { data, setData, put, processing, errors } = useForm<EditFormData>({
        title: testCase.title,
        tested_method: testCase.tested_method ?? '',
        description: testCase.description ?? '',
        status_id: testCase.status ? String(testCase.status.id) : '',
        assigned_to: testCase.assignee ? String(testCase.assignee.id) : '',
        steps: (testCase.steps.length > 0
            ? testCase.steps.map((step) => ({
                  description: step.description,
                  expected_result: step.expected_result ?? '',
              }))
            : [{ ...emptyStep }]) as StepForm[],
    });

    const stepErrors = errors as Record<string, string | undefined>;

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
        put(update.url(testCase));
    }

    setLayoutProps({
        breadcrumbs: [
            { title: 'Test cases', href: index() },
            { title: testCase.title, href: show(testCase.id) },
            { title: 'Edit', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${testCase.title}`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 pb-24">
                <Heading
                    title="Edit test case"
                    description="Edit only the test case specification: title, method, input, and expected output."
                />

                {hasExecutions && (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        This test case has previous executions. Saving a change
                        will create version {version + 1}; previous results will
                        remain linked to version {version}.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="tested_method">
                                    Method/function tested
                                </Label>

                                <Input
                                    id="tested_method"
                                    value={data.tested_method}
                                    onChange={(e) =>
                                        setData('tested_method', e.target.value)
                                    }
                                    placeholder="e.g. UserService.validatePassword()"
                                />

                                <InputError message={errors.tested_method} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>

                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    autoFocus
                                />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>

                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
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
                                    <SelectTrigger id="status_id">
                                        <SelectValue placeholder="Select a status" />
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
                                <InputError message={errors.status_id} />
                            </div>

                            {assignableUsers.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="assigned_to">
                                        Assigned to
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
                                        <SelectTrigger id="assigned_to">
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
                                                    {user.role &&
                                                        ` · ${user.role}`}
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
                                <Label htmlFor="step-input">Input</Label>

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
                                />

                                <InputError
                                    message={stepErrors['steps.0.description']}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="step-expected">
                                    Expected output
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
                                                onClick={() =>
                                                    removeStep(stepIndex)
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`step-description-${stepIndex}`}
                                        >
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
                                        <Label
                                            htmlFor={`step-expected-${stepIndex}`}
                                        >
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

                    <Card>
                        <CardContent className="flex flex-col gap-3">
                            <div>
                                <h2 className="text-sm font-semibold">
                                    Linked requirements (
                                    {linkedRequirements.length})
                                </h2>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    Linking and unlinking happen on the test
                                    case page, so they stay in one place.
                                </p>
                            </div>

                            {linkedRequirements.length > 0 ? (
                                <ul className="flex flex-col gap-1">
                                    {linkedRequirements.map((requirement) => (
                                        <li
                                            key={requirement.id}
                                            className="text-sm"
                                        >
                                            <span className="font-medium">
                                                {requirement.code}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                {requirement.title}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No requirements linked.
                                </p>
                            )}

                            <Button
                                type="button"
                                variant="link"
                                asChild
                                className="w-fit px-0"
                            >
                                <Link href={show(testCase.id)}>
                                    Manage on the test case page
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="fixed inset-x-0 bottom-0 z-10 border-t bg-background/95 backdrop-blur">
                        <div className="mx-auto flex w-full max-w-3xl items-center justify-end gap-3 px-4 py-3">
                            <Button type="button" variant="secondary" asChild>
                                <Link href={show(testCase.id)}>Cancel</Link>
                            </Button>

                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}
