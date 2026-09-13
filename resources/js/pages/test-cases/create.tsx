import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import StepsEditor, { emptyStep  } from '@/components/steps-editor';
import type {StepFormValue} from '@/components/steps-editor';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
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
import { Textarea } from '@/components/ui/textarea';
import { index, store } from '@/routes/test-cases';
import { show as templateShow } from '@/routes/test-templates';
import type { Classification, TestCaseStatus, TestTemplate, TestTemplateData } from '@/types';

type Props = {
    classifications: Classification[];
    templates: TestTemplate[];
    statuses: TestCaseStatus[];
};

const NO_TEMPLATE = 'none';

export default function CreateTestCase({ classifications, templates, statuses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        classification_id: '',
        template_id: '',
        status_id: '',
        steps: [{ ...emptyStep }] as StepFormValue[],
    });

    const stepErrors = errors as Record<string, string | undefined>;

    const [pendingTemplateId, setPendingTemplateId] = useState<string | null>(null);
    const [applyingTemplate, setApplyingTemplate] = useState(false);
    const [templateError, setTemplateError] = useState<string | null>(null);

    /**
     * Whether the user already typed something the template would overwrite.
     */
    function formHasContent(): boolean {
        return (
            data.description.trim() !== '' ||
            data.steps.some((step) => step.description.trim() !== '' || step.expected_result.trim() !== '')
        );
    }

    function handleTemplateChange(value: string) {
        setTemplateError(null);

        if (value === NO_TEMPLATE) {
            setData('template_id', '');

            return;
        }

        if (formHasContent()) {
            setPendingTemplateId(value);

            return;
        }

        void applyTemplate(value);
    }

    /**
     * Copy the template's description, classification and steps into the form.
     * The test case keeps its own copy, so later edits to the template do not
     * affect it.
     */
    async function applyTemplate(templateId: string) {
        setApplyingTemplate(true);

        try {
            const response = await fetch(templateShow.url(Number(templateId)), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('request failed');
            }

            const template: TestTemplateData = await response.json();

            setData({
                ...data,
                template_id: templateId,
                description: template.description ?? '',
                classification_id: template.classification_id
                    ? String(template.classification_id)
                    : data.classification_id,
                steps:
                    template.steps.length > 0
                        ? template.steps.map((step) => ({
                              description: step.description,
                              expected_result: step.expected_result ?? '',
                          }))
                        : [{ ...emptyStep }],
            });
        } catch {
            setTemplateError('Could not load the template. Please try again.');
        } finally {
            setApplyingTemplate(false);
            setPendingTemplateId(null);
        }
    }

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

                                <div className="grid gap-2">
                                    <Label htmlFor="status_id">Status</Label>
                                    <Select
                                        value={data.status_id}
                                        onValueChange={(value) => setData('status_id', value)}
                                    >
                                        <SelectTrigger id="status_id" className="w-full">
                                            <SelectValue placeholder="Select a status" />
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
                        <CardContent>
                            <StepsEditor
                                steps={data.steps}
                                onChange={(steps) => setData('steps', steps)}
                                errors={stepErrors}
                                disabled={applyingTemplate}
                            />
                        </CardContent>
                    </Card>

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