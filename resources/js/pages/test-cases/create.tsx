import { Head, useForm } from '@inertiajs/react';
// import { Plus, Trash2 } from 'lucide-react'; // desativado: steps múltiplos fora de uso (testes unitários/integração usam só entrada e saída esperada)
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import StepsEditor, { emptyStep  } from '@/components/steps-editor';
import type {StepFormValue} from '@/components/steps-editor';
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
import { index, store } from '@/routes/test-cases';
import type {
    Classification,
    RequirementOption,
    TestCaseStatus,
    TestTemplate,
} from '@/types';


type Props = {
    classifications: Classification[];
    templates: TestTemplate[];
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
    classifications,
    // templates, // desativado: templates fora de uso
    statuses,
    defaultStatusId,
    availableRequirements,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        classification_id: '',
        template_id: '',
        status_id: String(defaultStatusId),
        steps: [{ ...emptyStep }] as StepForm[],
        requirement_ids: [] as number[],
    });

    const [linkRequirementOpen, setLinkRequirementOpen] = useState(false);
    const [selectedRequirementId, setSelectedRequirementId] = useState('');

    const linkedRequirements = data.requirement_ids
        .map((id) => availableRequirements.find((requirement) => requirement.id === id))
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

    function updateStep(stepIndex: number, field: keyof StepForm, value: string) {
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
                    description="Define the general information and execution steps for the test case."
                />

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    autoFocus
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="classification_id">Classification</Label>
                                    <Select
                                        value={data.classification_id}
                                        onValueChange={(value) =>
                                            setData('classification_id', value)
                                        }
                                    >
                                        <SelectTrigger id="classification_id" className="w-full">
                                            <SelectValue placeholder="Select a classification" />
                                        </SelectTrigger>
                                        <SelectContent>
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
                                    <InputError message={errors.classification_id} />
                                </div>

                                {/* Template de caso de teste desativado (funcionalidade fora de uso)
                                <div className="grid gap-2">
                                    <Label htmlFor="template_id">
                                        Template (optional)
                                    </Label>
                                    <Select
                                        value={data.template_id || NO_TEMPLATE}
                                        onValueChange={handleTemplateChange}
                                        disabled={applyingTemplate}
                                    >
                                        <SelectTrigger id="template_id" className="w-full">
                                            <SelectValue placeholder="No template" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NO_TEMPLATE}>
                                                <span className="text-muted-foreground">No template</span>
                                            </SelectItem>
                                            {templates.map((template) => (
                                                <SelectItem
                                                    key={template.id}
                                                    value={String(template.id)}
                                                >
                                                    {template.title}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-muted-foreground">
                                        Copies the template's description and steps into this form.
                                    </p>
                                    <InputError message={templateError ?? errors.template_id} />
                                </div>
                                */}

                                <div className="grid gap-2">
                                    <Label htmlFor="status_id">Status</Label>
                                    <Select
                                        value={data.status_id}
                                        onValueChange={(value) => setData('status_id', value)}
                                    >
                                        <SelectTrigger id="status_id" className="w-full">
                                            <SelectValue />
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
                                        updateStep(0, 'description', e.target.value)
                                    }
                                />

                                <InputError
                                    message={stepErrors['steps.0.description']}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="step-expected">Expected output</Label>

                                <Textarea
                                    id="step-expected"
                                    value={data.steps[0].expected_result}
                                    onChange={(e) =>
                                        updateStep(0, 'expected_result', e.target.value)
                                    }
                                />

                                <InputError
                                    message={stepErrors['steps.0.expected_result']}
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
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>
                                    Linked requirements ({linkedRequirements.length})
                                </CardTitle>

                                {linkableRequirements.length > 0 && (
                                    <Dialog
                                        open={linkRequirementOpen}
                                        onOpenChange={setLinkRequirementOpen}
                                    >
                                        <DialogTrigger asChild>
                                            <Button type="button" variant="outline" size="sm">
                                                Link requirement
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogTitle>Link requirement</DialogTitle>
                                            <DialogDescription>
                                                Select a requirement to link to this test case.
                                            </DialogDescription>

                                            <div className="flex flex-col gap-4">
                                                <Select
                                                    value={selectedRequirementId}
                                                    onValueChange={setSelectedRequirementId}
                                                >
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select a requirement" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {linkableRequirements.map((requirement) => (
                                                            <SelectItem
                                                                key={requirement.id}
                                                                value={String(requirement.id)}
                                                            >
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
                                                    <Button
                                                        type="button"
                                                        disabled={!selectedRequirementId}
                                                        onClick={submitLinkRequirement}
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
                                                onClick={() => unlinkRequirement(requirement.id)}
                                            >
                                                Unlink
                                            </Button>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing || applyingTemplate}>
                            Create test case
                        </Button>
                    </div>
                </form>
            </div>

            <Dialog
                open={pendingTemplateId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingTemplateId(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogTitle>Apply template?</DialogTitle>
                    <DialogDescription>
                        The description and steps you typed will be replaced with the template's content.
                    </DialogDescription>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="secondary" onClick={() => setPendingTemplateId(null)}>
                            Keep my changes
                        </Button>
                        <Button
                            type="button"
                            disabled={applyingTemplate}
                            onClick={() => pendingTemplateId && void applyTemplate(pendingTemplateId)}
                        >
                            {applyingTemplate ? 'Applying...' : 'Replace'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CreateTestCase.layout = {
    breadcrumbs: [
        { title: 'Test cases', href: index() },
        { title: 'Create', href: '#' },
    ],
};