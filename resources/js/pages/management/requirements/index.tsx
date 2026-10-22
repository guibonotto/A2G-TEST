// import { Form, Head, Link, useForm, usePage } from '@inertiajs/react';
import { Form, Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChevronDown, FileText, MoreHorizontal, Plus } from 'lucide-react';
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
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { edit as jiraEdit } from '@/routes/jira';
import { index } from '@/routes/requirements';
import type {
    RequirementListItem,
    RequirementPriority,
    RequirementStatus,
    RequirementType,
} from '@/types';

type Props = {
    requirements: RequirementListItem[];
};

type RequirementFormData = {
    code: string;
    type: RequirementType;
    title: string;
    description: string;
    priority: RequirementPriority;
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

/** The status follows coverage: computed by the server, never typed in. */
const statusBadges: Record<
    RequirementStatus,
    { label: string; variant: 'warning' | 'info' | 'success'; hint: string }
> = {
    pendente: {
        label: 'Pending',
        variant: 'warning',
        hint: 'No test case covers this requirement yet.',
    },
    em_andamento: {
        label: 'In progress',
        variant: 'info',
        hint: 'Some linked test cases have not passed their latest run.',
    },
    concluido: {
        label: 'Done',
        variant: 'success',
        hint: 'Every linked test case passed its latest run.',
    },
};

const DEFAULT_PRIORITY: RequirementPriority = 'media';

/**
 * Sort by the numeric tail of the code instead of lexicographically, so
 * PROJ-2 comes before PROJ-19.
 */
function compareCodes(a: string, b: string): number {
    const parse = (code: string): [string, number] => {
        const match = /^(.*?)(\d+)$/.exec(code.trim());

        return match ? [match[1], Number(match[2])] : [code, 0];
    };

    const [prefixA, numberA] = parse(a);
    const [prefixB, numberB] = parse(b);

    return prefixA === prefixB
        ? numberA - numberB
        : prefixA.localeCompare(prefixB);
}

const typeLabels: Record<string, string> = {
    funcional: 'Functional',
    nao_funcional: 'Non-functional',
};

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
                    <Label htmlFor={`${idPrefix}-code`}>Code (required)</Label>
                    <Input
                        id={`${idPrefix}-code`}
                        value={form.data.code}
                        onChange={(e) => form.setData('code', e.target.value)}
                        placeholder="e.g. RF-001"
                        autoFocus
                    />
                    <InputError message={form.errors.code} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-type`}>Type</Label>
                    <Select
                        value={form.data.type}
                        onValueChange={(value) =>
                            form.setData('type', value as RequirementType)
                        }
                    >
                        <SelectTrigger
                            id={`${idPrefix}-type`}
                            className="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {typeOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.type} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-title`}>Title (required)</Label>
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
                    onChange={(e) =>
                        form.setData('description', e.target.value)
                    }
                    rows={3}
                />
                <InputError message={form.errors.description} />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-priority`}>Priority</Label>
                    <Select
                        value={form.data.priority}
                        onValueChange={(value) =>
                            form.setData(
                                'priority',
                                value as RequirementPriority,
                            )
                        }
                    >
                        <SelectTrigger
                            id={`${idPrefix}-priority`}
                            className="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {priorityOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.priority} />
                </div>

                <div className="grid gap-2">
                    <Label>Status</Label>
                    <p className="text-sm text-muted-foreground">
                        Set automatically from the test cases linked to this
                        requirement.
                    </p>
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
    const projectName = usePage().props.currentProject?.name ?? 'this project';
    const [createOpen, setCreateOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [uncoveredOnly, setUncoveredOnly] = useState(false);

    const createForm = useForm<RequirementFormData>({
        code: '',
        type: 'funcional',
        title: '',
        description: '',
        priority: DEFAULT_PRIORITY,
    });

    const uncoveredCount = requirements.filter(
        (requirement) => requirement.test_cases_count === 0,
    ).length;

    const term = search.trim().toLowerCase();

    const visibleRequirements = requirements
        .filter((requirement) =>
            term === ''
                ? true
                : requirement.code.toLowerCase().includes(term) ||
                  requirement.title.toLowerCase().includes(term),
        )
        .filter((requirement) =>
            uncoveredOnly ? requirement.test_cases_count === 0 : true,
        )
        .sort((a, b) => compareCodes(a.code, b.code));

    /** A column repeating the same value in every row informs nothing. */
    const showType =
        new Set(requirements.map((requirement) => requirement.type)).size > 1;

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
                        description={`Requirements of ${projectName} that can be linked to test cases.`}
                    />

                    {/* Most requirements arrive from Jira, but that path used
                        to live on another screen entirely. */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button>
                                <Plus /> Add requirement
                                <ChevronDown className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={jiraEdit()}>Import from Jira</Link>
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onSelect={() => setCreateOpen(true)}
                            >
                                Create manually
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogContent>
                            <DialogTitle>New requirement</DialogTitle>
                            <DialogDescription>
                                Register a functional or non-functional
                                requirement in <strong>{projectName}</strong>.
                            </DialogDescription>
                            <RequirementForm
                                form={createForm}
                                idPrefix="create"
                                onSubmit={submitCreate}
                            />
                        </DialogContent>
                    </Dialog>
                </div>

                {requirements.length > 0 && (
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by code or title..."
                            className="sm:max-w-xs"
                        />

                        <Button
                            type="button"
                            variant={uncoveredOnly ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setUncoveredOnly((value) => !value)}
                        >
                            No coverage ({uncoveredCount})
                        </Button>

                        <span className="text-sm text-muted-foreground">
                            {visibleRequirements.length} of{' '}
                            {requirements.length}
                        </span>
                    </div>
                )}

                <Card className="overflow-hidden py-0">
                    {requirements.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 py-12 text-center">
                            <FileText
                                className="size-8 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <p className="text-sm font-medium">
                                No requirements yet
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Import them from Jira or create one manually to
                                start linking test cases.
                            </p>
                        </div>
                    ) : visibleRequirements.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            No requirements match this search.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Code
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Title
                                    </th>
                                    {showType && (
                                        <th className="px-4 py-3 font-medium">
                                            Type
                                        </th>
                                    )}
                                    <th className="px-4 py-3 font-medium">
                                        Coverage
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {visibleRequirements.map((requirement) => (
                                    <RequirementRow
                                        key={requirement.id}
                                        requirement={requirement}
                                        showType={showType}
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

function RequirementRow({
    requirement,
    showType,
}: {
    requirement: RequirementListItem;
    showType: boolean;
}) {
    const [editOpen, setEditOpen] = useState(false);
    const projectName = usePage().props.currentProject?.name ?? 'this project';
    const [deleteOpen, setDeleteOpen] = useState(false);

    const editForm = useForm<RequirementFormData>({
        code: requirement.code,
        type: requirement.type,
        title: requirement.title,
        description: requirement.description ?? '',
        priority: requirement.priority ?? DEFAULT_PRIORITY,
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
            {showType && (
                <td className="px-4 py-3">
                    <Badge variant="secondary">
                        {typeLabels[requirement.type] ?? requirement.type}
                    </Badge>
                </td>
            )}
            <td className="px-4 py-3">
                {requirement.test_cases_count === 0 ? (
                    <Badge variant="warning">No coverage</Badge>
                ) : (
                    `${requirement.test_cases_count} case(s)`
                )}
            </td>
            <td className="px-4 py-3">
                <Badge
                    variant={statusBadges[requirement.status].variant}
                    title={statusBadges[requirement.status].hint}
                >
                    {statusBadges[requirement.status].label}
                </Badge>
            </td>
            <td className="px-4 py-3 text-right">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Actions for ${requirement.code}`}
                        >
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onSelect={() => setEditOpen(true)}>
                            Edit
                        </DropdownMenuItem>

                        <DropdownMenuItem
                            onSelect={() => setDeleteOpen(true)}
                            className="text-destructive focus:text-destructive"
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <div className="text-left">
                    <Dialog open={editOpen} onOpenChange={setEditOpen}>
                        <DialogContent>
                            <DialogTitle>Edit requirement</DialogTitle>
                            <DialogDescription>
                                Update the details of &quot;{requirement.code}
                                &quot; in <strong>{projectName}</strong>.
                            </DialogDescription>
                            <RequirementForm
                                form={editForm}
                                idPrefix={`edit-${requirement.id}`}
                                onSubmit={submitEdit}
                            />
                        </DialogContent>
                    </Dialog>

                    <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                        <DialogContent>
                            <DialogTitle>Delete requirement?</DialogTitle>
                            <DialogDescription>
                                This action cannot be undone.{' '}
                                {requirement.test_cases_count > 0
                                    ? `${requirement.test_cases_count} test case(s) linked to ${requirement.code} will lose that association and be left without a requirement.`
                                    : `${requirement.code} has no linked test cases.`}
                            </DialogDescription>

                            <Form
                                {...RequirementController.destroy.form(
                                    requirement,
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
                </div>
            </td>
        </tr>
    );
}

ManageRequirements.layout = {
    breadcrumbs: [{ title: 'Manage requirements', href: index() }],
};
