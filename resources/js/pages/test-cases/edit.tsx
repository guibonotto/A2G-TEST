import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
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
import type {
    Classification,
    TestCaseDetail,
    TestCaseStatus,
    TestTemplate,
} from '@/types';

type Props = {
    testCase: TestCaseDetail;
    classifications: Classification[];
    templates: TestTemplate[];
    statuses: TestCaseStatus[];
};

type StepForm = {
    description: string;
    expected_result: string;
};

const emptyStep: StepForm = { description: '', expected_result: '' };

export default function EditTestCase({
    testCase,
    classifications,
    templates,
    statuses,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        title: testCase.title,
        description: testCase.description ?? '',
        classification_id: testCase.classification ? String(testCase.classification.id) : '',
        template_id: testCase.template ? String(testCase.template.id) : '',
        status_id: testCase.status ? String(testCase.status.id) : '',
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

    function addStep() {
        setData('steps', [...data.steps, { ...emptyStep }]);
    }

    function removeStep(stepIndex: number) {
        setData(
            'steps',
            data.steps.filter((_, i) => i !== stepIndex),
        );
    }

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

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Edit test case"
                    description="Update the general information and execution steps of the test case."
                />

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex flex-col gap-4">
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
                                <Label htmlFor="description">
                                    Description
                                </Label>

                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                />

                                <InputError
                                    message={errors.description}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="classification_id">
                                        Classification
                                    </Label>

                                    <Select
                                        value={data.classification_id}
                                        onValueChange={(value) =>
                                            setData(
                                                'classification_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="classification_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select a classification" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {classifications.map(
                                                (classification) => (
                                                    <SelectItem
                                                        key={classification.id}
                                                        value={String(
                                                            classification.id,
                                                        )}
                                                    >
                                                        {classification.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        message={errors.classification_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="template_id">
                                        Template (optional)
                                    </Label>

                                    <Select
                                        value={data.template_id}
                                        onValueChange={(value) =>
                                            setData(
                                                'template_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="template_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="No template" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {templates.map((template) => (
                                                <SelectItem
                                                    key={template.id}
                                                    value={String(
                                                        template.id,
                                                    )}
                                                >
                                                    {template.title}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        message={errors.template_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status_id">Status</Label>
                                    <Select
                                        value={data.status_id}
                                        onValueChange={(value) => setData('status_id', value)}
                                    >
                                        <SelectTrigger id="status_id" className="w-full">
                                            <SelectValue placeholder="Selecione um status" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {statuses.map((status) => (
                                                <SelectItem key={status.id} value={String(status.id)}>
                                                    {status.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status_id} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex flex-col gap-4">
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
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}