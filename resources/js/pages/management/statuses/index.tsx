import { Form, Head, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import TestCaseStatusController from '@/actions/App/Http/Controllers/TestCaseStatusController';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { testCaseStatusLabel } from '@/lib/test-case-status';
import { index, store, update } from '@/routes/test-case-statuses';
import type {
    TestCaseStatus,
    TestCaseStatusCategory,
    TestCaseStatusColor,
} from '@/types';

type ManagedStatus = TestCaseStatus & { test_cases_count: number };

type Props = {
    statuses: ManagedStatus[];
};

const colorOptions: {
    value: TestCaseStatusColor;
    label: string;
    swatch: string;
}[] = [
    { value: 'success', label: 'Green', swatch: 'bg-success' },
    { value: 'destructive', label: 'Red', swatch: 'bg-destructive' },
    { value: 'warning', label: 'Amber', swatch: 'bg-warning' },
    { value: 'info', label: 'Teal', swatch: 'bg-info' },
    { value: 'secondary', label: 'Gray', swatch: 'bg-muted-foreground' },
    { value: 'blue', label: 'Blue', swatch: 'bg-blue-500' },
    { value: 'purple', label: 'Purple', swatch: 'bg-violet-500' },
    { value: 'orange', label: 'Orange', swatch: 'bg-orange-500' },
    { value: 'pink', label: 'Pink', swatch: 'bg-pink-500' },
];

const categoryOptions: { value: TestCaseStatusCategory; label: string }[] = [
    { value: 'in_progress', label: 'In progress' },
    { value: 'approved', label: 'Approved' },
    { value: 'failed', label: 'Failed' },
    { value: 'cancelled', label: 'Cancelled' },
];

function normalizeStatusName(value: string): string {
    const name = value.trim().toLowerCase();

    return name ? name.charAt(0).toUpperCase() + name.slice(1) : '';
}

/**
 * Swatches instead of a dropdown of colour names: the user no longer has to
 * imagine what "Teal" or "Amber" look like, and colours already taken by
 * another status are called out so two statuses do not end up identical.
 */
function ColorPicker({
    value,
    onChange,
    usedBy,
    id,
}: {
    value: TestCaseStatusColor;
    onChange: (value: TestCaseStatusColor) => void;
    usedBy: Partial<Record<TestCaseStatusColor, string>>;
    id: string;
}) {
    return (
        <div id={id} className="flex flex-wrap gap-2" role="radiogroup">
            {colorOptions.map((option) => {
                const taken = usedBy[option.value];
                const selected = value === option.value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        onClick={() => onChange(option.value)}
                        title={
                            taken
                                ? `${option.label} — already used by ${taken}`
                                : option.label
                        }
                        className={cn(
                            'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors',
                            selected
                                ? 'border-primary bg-primary/8'
                                : 'border-input hover:border-primary/40',
                        )}
                    >
                        <span
                            className={cn(
                                'size-3.5 rounded-full',
                                option.swatch,
                            )}
                            aria-hidden="true"
                        />
                        <span>{option.label}</span>
                        {taken && (
                            <span className="text-xs text-muted-foreground">
                                in use
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}

export default function ManageTestCaseStatuses({ statuses }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const usedBy = statuses.reduce<
        Partial<Record<TestCaseStatusColor, string>>
    >((map, status) => {
        map[status.color] ??= status.name;

        return map;
    }, {});

    /** Default to a colour nobody is using, so a new status is not born looking like "Cancelled". */
    const firstFreeColor =
        colorOptions.find((option) => !usedBy[option.value])?.value;

    const createForm = useForm({
        name: '',
        description: '',
        color: (firstFreeColor ?? 'secondary') as TestCaseStatusColor,
        category: 'in_progress' as TestCaseStatusCategory,
    });

    const duplicateName = statuses.some(
        (status) =>
            status.name.trim().toLowerCase() ===
            createForm.data.name.trim().toLowerCase(),
    );

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
            <Head title="Manage statuses" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Manage statuses"
                        description="Statuses of the current project. Each project has its own set, so changes here do not affect other projects."
                    />

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus /> New status
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>New status</DialogTitle>
                            <DialogDescription>
                                Set the name and color of the new status.
                            </DialogDescription>

                            <form
                                onSubmit={submitCreate}
                                className="flex flex-col gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="create-name">Name</Label>
                                    <Input
                                        id="create-name"
                                        value={createForm.data.name}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        onBlur={() =>
                                            createForm.setData(
                                                'name',
                                                normalizeStatusName(
                                                    createForm.data.name,
                                                ),
                                            )
                                        }
                                        placeholder="e.g. Blocked"
                                        autoFocus
                                        aria-invalid={
                                            createForm.errors.name ||
                                            duplicateName
                                                ? 'true'
                                                : 'false'
                                        }
                                    />
                                    {duplicateName ? (
                                        <InputError message="A status with this name already exists." />
                                    ) : (
                                        <InputError
                                            message={createForm.errors.name}
                                        />
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-description">
                                        Description (when to use)
                                    </Label>
                                    <Input
                                        id="create-description"
                                        value={createForm.data.description}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Waiting for an external dependency"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-category">
                                        This status indicates
                                    </Label>
                                    <select
                                        id="create-category"
                                        value={createForm.data.category}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'category',
                                                e.target.value as TestCaseStatusCategory,
                                            )
                                        }
                                        className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        {categoryOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={createForm.errors.category} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-color">Color</Label>
                                    <ColorPicker
                                        id="create-color"
                                        value={createForm.data.color}
                                        usedBy={usedBy}
                                        onChange={(value) =>
                                            createForm.setData('color', value)
                                        }
                                    />
                                    {!firstFreeColor && (
                                        <p className="text-sm text-destructive">
                                            All available colors are in use. Edit an existing status to free one before creating another.
                                        </p>
                                    )}
                                    <InputError
                                        message={createForm.errors.color}
                                    />
                                </div>

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
                                            createForm.processing ||
                                            duplicateName ||
                                            !firstFreeColor
                                        }
                                    >
                                        Create status
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="overflow-hidden py-0">
                    {statuses.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            No statuses registered yet.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Test cases
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {statuses.map((status) => (
                                    <StatusRow
                                        key={status.id}
                                        status={status}
                                        usedBy={usedBy}
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

function StatusRow({
    status,
    usedBy,
}: {
    status: ManagedStatus;
    usedBy: Partial<Record<TestCaseStatusColor, string>>;
}) {
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const editForm = useForm({
        name: status.name,
        description: status.description ?? '',
        color: status.color,
        category: status.category,
    });
    const inUse = status.test_cases_count > 0;

    function submitEdit(e: FormEvent) {
        e.preventDefault();
        editForm.put(update.url(status), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    }

    return (
        <tr className="border-b last:border-0 hover:bg-muted/50">
            <td className="px-4 py-3">
                <Badge variant={status.color}>
                    {testCaseStatusLabel(status.name)}
                </Badge>
            </td>
            <td className="px-4 py-3">{status.test_cases_count}</td>
            <td className="px-4 py-3 text-right">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Actions for ${status.name}`}
                        >
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onSelect={() => setEditOpen(true)}>
                            Edit
                        </DropdownMenuItem>

                        <DropdownMenuItem
                            disabled={inUse}
                            onSelect={() => setDeleteOpen(true)}
                            className="text-destructive focus:text-destructive"
                        >
                            {inUse
                                ? `In use by ${status.test_cases_count} case(s)`
                                : 'Delete'}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Dialog open={editOpen} onOpenChange={setEditOpen}>
                    <DialogContent>
                        <DialogTitle>Edit status</DialogTitle>
                        <DialogDescription>
                            Update the name and color of &ldquo;{status.name}
                            &rdquo;.
                        </DialogDescription>

                        <form
                            onSubmit={submitEdit}
                            className="flex flex-col gap-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor={`edit-name-${status.id}`}>
                                    Name
                                </Label>
                                <Input
                                    id={`edit-name-${status.id}`}
                                    value={editForm.data.name}
                                    onChange={(e) =>
                                        editForm.setData('name', e.target.value)
                                    }
                                    onBlur={() =>
                                        editForm.setData(
                                            'name',
                                            normalizeStatusName(editForm.data.name),
                                        )
                                    }
                                    autoFocus
                                    aria-invalid={
                                        editForm.errors.name ? 'true' : 'false'
                                    }
                                />
                                <InputError message={editForm.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`edit-description-${status.id}`}>
                                    Description (when to use)
                                </Label>
                                <Input
                                    id={`edit-description-${status.id}`}
                                    value={editForm.data.description}
                                    onChange={(e) =>
                                        editForm.setData('description', e.target.value)
                                    }
                                />
                                <InputError message={editForm.errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`edit-category-${status.id}`}>
                                    This status indicates
                                </Label>
                                <select
                                    id={`edit-category-${status.id}`}
                                    value={editForm.data.category}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'category',
                                            e.target.value as TestCaseStatusCategory,
                                        )
                                    }
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {categoryOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={editForm.errors.category} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`edit-color-${status.id}`}>
                                    Color
                                </Label>
                                <ColorPicker
                                    id={`edit-color-${status.id}`}
                                    value={editForm.data.color}
                                    usedBy={usedBy}
                                    onChange={(value) =>
                                        editForm.setData('color', value)
                                    }
                                />
                                <InputError message={editForm.errors.color} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
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

                <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                    <DialogContent>
                        <DialogTitle>Delete status?</DialogTitle>
                        <DialogDescription>
                            This action cannot be undone. Test cases with the
                            &ldquo;{status.name}&rdquo; status will be left
                            without a status.
                        </DialogDescription>

                        <Form
                            {...TestCaseStatusController.destroy.form(status)}
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
            </td>
        </tr>
    );
}

ManageTestCaseStatuses.layout = {
    breadcrumbs: [{ title: 'Manage statuses', href: index() }],
};
