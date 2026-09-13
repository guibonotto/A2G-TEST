import { Form, Head, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import TestTemplateController from '@/actions/App/Http/Controllers/TestTemplateController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import StepsEditor, { emptyStep } from '@/components/steps-editor';
import type { StepFormValue } from '@/components/steps-editor';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
import { index, store, update } from '@/routes/test-templates';
import type { Classification, TestTemplate, TestTemplateStep } from '@/types';

type ManagedTemplate = TestTemplate & {
    description: string | null;
    classification_id: number | null;
    classification: Classification | null;
    creator: { id: number; name: string } | null;
    steps: TestTemplateStep[];
    test_cases_count: number;
};

type Props = {
    templates: ManagedTemplate[];
    classifications: Classification[];
};

type TemplateFormData = {
    title: string;
    description: string;
    classification_id: string;
    steps: StepFormValue[];
};

type FieldErrors = Record<string, string | undefined>;

const NO_CLASSIFICATION = 'none';

function toFormSteps(steps: TestTemplateStep[]): StepFormValue[] {
    if (steps.length === 0) {
        return [{ ...emptyStep }];
    }

    return steps.map((step) => ({
        description: step.description,
        expected_result: step.expected_result ?? '',
    }));
}

/**
 * Fields shared by the create and edit dialogs.
 */
function TemplateFields({
    form,
    classifications,
    idPrefix,
}: {
    form: ReturnType<typeof useForm<TemplateFormData>>;
    classifications: Classification[];
    idPrefix: string;
}) {
    const errors = form.errors as FieldErrors;

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-title`}>Title</Label>
                <Input
                    id={`${idPrefix}-title`}
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                    autoFocus
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-description`}>Description</Label>
                <Textarea
                    id={`${idPrefix}-description`}
                    value={form.data.description}
                    onChange={(e) =>
                        form.setData('description', e.target.value)
                    }
                    placeholder="Copied into the test case description when the template is applied."
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-classification`}>
                    Default classification
                </Label>
                <Select
                    value={form.data.classification_id || NO_CLASSIFICATION}
                    onValueChange={(value) =>
                        form.setData(
                            'classification_id',
                            value === NO_CLASSIFICATION ? '' : value,
                        )
                    }
                >
                    <SelectTrigger
                        id={`${idPrefix}-classification`}
                        className="w-full"
                    >
                        <SelectValue placeholder="No default" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NO_CLASSIFICATION}>
                            <span className="text-muted-foreground">
                                No default
                            </span>
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
                <InputError message={errors.classification_id} />
            </div>

            <StepsEditor
                steps={form.data.steps}
                onChange={(steps) => form.setData('steps', steps)}
                errors={errors}
                idPrefix={`${idPrefix}-step`}
                description="These steps are copied into every test case created from this template."
                disabled={form.processing}
            />
        </>
    );
}

export default function ManageTestTemplates({
    templates,
    classifications,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const createForm = useForm<TemplateFormData>({
        title: '',
        description: '',
        classification_id: '',
        steps: [{ ...emptyStep }],
    });

    function submitCreate(e: FormEvent) {
        e.preventDefault();
        createForm.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setCreateOpen(false);
            },
        });
    }

    return (
        <>
            <Head title="Manage templates" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Manage templates"
                        description="Reusable descriptions and steps that pre-fill new test cases."
                    />

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus /> New template
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                            <DialogTitle>New template</DialogTitle>
                            <DialogDescription>
                                Selecting this template on a new test case
                                copies its description, classification and steps
                                into the form.
                            </DialogDescription>

                            <form
                                onSubmit={submitCreate}
                                className="flex flex-col gap-4"
                            >
                                <TemplateFields
                                    form={createForm}
                                    classifications={classifications}
                                    idPrefix="create"
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
                                        disabled={createForm.processing}
                                    >
                                        Create template
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="overflow-hidden py-0">
                    {templates.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            No templates registered yet.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Template
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Classification
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Steps
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Test cases
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Created by
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {templates.map((template) => (
                                    <TemplateRow
                                        key={template.id}
                                        template={template}
                                        classifications={classifications}
                                    />
                                ))}
                            </tbody>
                        </table>
                    )}
                </Card>
            </div>
        </>
    );
}

function TemplateRow({
    template,
    classifications,
}: {
    template: ManagedTemplate;
    classifications: Classification[];
}) {
    const [editOpen, setEditOpen] = useState(false);

    const editForm = useForm<TemplateFormData>({
        title: template.title,
        description: template.description ?? '',
        classification_id: template.classification_id
            ? String(template.classification_id)
            : '',
        steps: toFormSteps(template.steps),
    });

    function submitEdit(e: FormEvent) {
        e.preventDefault();
        editForm.put(update.url(template), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    }

    return (
        <tr className="border-b last:border-0 hover:bg-muted/50">
            <td className="px-4 py-3">
                <div className="flex flex-col">
                    <span className="font-medium">{template.title}</span>
                    {template.description && (
                        <span className="line-clamp-1 text-xs text-muted-foreground">
                            {template.description}
                        </span>
                    )}
                </div>
            </td>
            <td className="px-4 py-3 text-muted-foreground">
                {template.classification?.name ?? '—'}
            </td>
            <td className="px-4 py-3">{template.steps.length}</td>
            <td className="px-4 py-3">{template.test_cases_count}</td>
            <td className="px-4 py-3 text-muted-foreground">
                {template.creator?.name ?? 'Deleted user'}
            </td>
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <Dialog open={editOpen} onOpenChange={setEditOpen}>
                        <DialogTrigger asChild>
                            <Button variant="outline" size="sm">
                                Edit
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                            <DialogTitle>Edit template</DialogTitle>
                            <DialogDescription>
                                Changes apply to test cases created from now on.
                                Existing test cases keep their own steps.
                            </DialogDescription>

                            <form
                                onSubmit={submitEdit}
                                className="flex flex-col gap-4"
                            >
                                <TemplateFields
                                    form={editForm}
                                    classifications={classifications}
                                    idPrefix={`edit-${template.id}`}
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
                                        disabled={editForm.processing}
                                    >
                                        Save
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive" size="sm">
                                Delete
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Delete template?</DialogTitle>
                            <DialogDescription>
                                {template.test_cases_count > 0
                                    ? `${template.test_cases_count} test case(s) were created from "${template.title}". They keep their steps and only lose the reference to this template.`
                                    : `"${template.title}" will be permanently removed.`}
                            </DialogDescription>

                            <Form
                                {...TestTemplateController.destroy.form(
                                    template,
                                )}
                                options={{ preserveScroll: true }}
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
                </div>
            </td>
        </tr>
    );
}

ManageTestTemplates.layout = {
    breadcrumbs: [{ title: 'Manage templates', href: index() }],
};
