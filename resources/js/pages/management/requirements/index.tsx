import { Form, Head, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import RequirementController from '@/actions/App/Http/Controllers/RequirementController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { index } from '@/routes/requirements';
import type { RequirementListItem, RequirementPriority, RequirementStatus, RequirementType } from '@/types';

type Props = {
    requirements: RequirementListItem[];
};

type RequirementFormData = {
    code: string;
    type: RequirementType;
    title: string;
    description: string;
    priority: string;
    status: string;
};

const typeOptions: { value: RequirementType; label: string }[] = [
    { value: 'funcional', label: 'Functional (FR)' },
    { value: 'nao_funcional', label: 'Non-functional (NFR)' },
];

const priorityOptions: { value: RequirementPriority; label: string }[] = [
    { value: 'baixa', label: 'Low' },
    { value: 'media', label: 'Medium' },
    { value: 'alta', label: 'High' },
];

const statusOptions: { value: RequirementStatus; label: string }[] = [
    { value: 'pendente', label: 'Pending' },
    { value: 'em_andamento', label: 'In progress' },
    { value: 'concluido', label: 'Done' },
];

const NONE = 'none';

function RequirementForm({
    form,
    idPrefix,
    onSubmit,
}: {
    form: ReturnType<typeof useForm<RequirementFormData>>;
    idPrefix: string;
    onSubmit: (e: FormEvent) => void;
}) {
    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-4">
            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-code`}>Code</Label>
                    <Input
                        id={`${idPrefix}-code`}
                        value={form.data.code}
                        onChange={(e) => form.setData('code', e.target.value)}
                        placeholder="RF032"
                        autoFocus
                    />
                    <InputError message={form.errors.code} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-type`}>Type</Label>
                    <Select value={form.data.type} onValueChange={(value) => form.setData('type', value as RequirementType)}>
                        <SelectTrigger id={`${idPrefix}-type`} className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {typeOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.type} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-title`}>Title</Label>
                <Input
                    id={`${idPrefix}-title`}
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                />
                <InputError message={form.errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-description`}>Description</Label>
                <Textarea
                    id={`${idPrefix}-description`}
                    value={form.data.description}
                    onChange={(e) => form.setData('description', e.target.value)}
                    rows={3}
                />
                <InputError message={form.errors.description} />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-priority`}>Priority</Label>
                    <Select
                        value={form.data.priority || NONE}
                        onValueChange={(value) => form.setData('priority', value === NONE ? '' : value)}
                    >
                        <SelectTrigger id={`${idPrefix}-priority`} className="w-full">
                            <SelectValue placeholder="No priority" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>No priority</SelectItem>
                            {priorityOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.priority} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-status`}>Status</Label>
                    <Select
                        value={form.data.status || NONE}
                        onValueChange={(value) => form.setData('status', value === NONE ? '' : value)}
                    >
                        <SelectTrigger id={`${idPrefix}-status`} className="w-full">
                            <SelectValue placeholder="No status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>No status</SelectItem>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.status} />
                </div>
            </div>

            <DialogFooter className="gap-2">
                <DialogClose asChild>
                    <Button type="button" variant="secondary">
                        Cancel
                    </Button>
                </DialogClose>
                <Button type="submit" disabled={form.processing}>
                    Save
                </Button>
            </DialogFooter>
        </form>
    );
}

export default function ManageRequirements({ requirements }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const createForm = useForm<RequirementFormData>({
        code: '',
        type: 'funcional',
        title: '',
        description: '',
        priority: '',
        status: '',
    });

    function submitCreate(e: FormEvent) {
        e.preventDefault();
        createForm.post(RequirementController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setCreateOpen(false);
            },
        });
    }

    return (
        <>
            <Head title="Manage requirements" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Manage requirements"
                        description="Functional and non-functional requirements that can be linked to test cases."
                    />

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus /> New requirement
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>New requirement</DialogTitle>
                            <DialogDescription>Register a functional or non-functional requirement.</DialogDescription>
                            <RequirementForm form={createForm} idPrefix="create" onSubmit={submitCreate} />
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="overflow-hidden py-0">
                    {requirements.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">No requirements registered yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Code</th>
                                    <th className="px-4 py-3 font-medium">Title</th>
                                    <th className="px-4 py-3 font-medium">Type</th>
                                    <th className="px-4 py-3 font-medium">Linked cases</th>
                                    <th className="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {requirements.map((requirement) => (
                                    <RequirementRow key={requirement.id} requirement={requirement} />
                                ))}
                            </tbody>
                        </table>
                    )}
                </Card>
            </div>
        </>
    );
}

function RequirementRow({ requirement }: { requirement: RequirementListItem }) {
    const [editOpen, setEditOpen] = useState(false);

    const editForm = useForm<RequirementFormData>({
        code: requirement.code,
        type: requirement.type,
        title: requirement.title,
        description: requirement.description ?? '',
        priority: requirement.priority ?? '',
        status: requirement.status ?? '',
    });

    function submitEdit(e: FormEvent) {
        e.preventDefault();
        editForm.put(RequirementController.update.url(requirement), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    }

    return (
        <tr className="border-b last:border-0 hover:bg-muted/50">
            <td className="px-4 py-3 font-medium">{requirement.code}</td>
            <td className="px-4 py-3">{requirement.title}</td>
            <td className="px-4 py-3">
                <Badge variant="secondary">{requirement.type === 'funcional' ? 'FR' : 'NFR'}</Badge>
            </td>
            <td className="px-4 py-3">{requirement.test_cases_count}</td>
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <Dialog open={editOpen} onOpenChange={setEditOpen}>
                        <DialogTrigger asChild>
                            <Button variant="outline" size="sm">
                                Edit
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Edit requirement</DialogTitle>
                            <DialogDescription>Update the details of &quot;{requirement.code}&quot;.</DialogDescription>
                            <RequirementForm form={editForm} idPrefix={`edit-${requirement.id}`} onSubmit={submitEdit} />
                        </DialogContent>
                    </Dialog>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive" size="sm">
                                Delete
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Delete requirement?</DialogTitle>
                            <DialogDescription>
                                This action cannot be undone. Test cases linked to &quot;{requirement.code}&quot; will lose
                                that association.
                            </DialogDescription>

                            <Form {...RequirementController.destroy.form(requirement)}>
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
            </td>
        </tr>
    );
}

ManageRequirements.layout = {
    breadcrumbs: [{ title: 'Manage requirements', href: index() }],
};
