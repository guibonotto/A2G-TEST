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
    { value: 'funcional', label: 'Funcional (RF)' },
    { value: 'nao_funcional', label: 'Não funcional (RNF)' },
];

const priorityOptions: { value: RequirementPriority; label: string }[] = [
    { value: 'baixa', label: 'Baixa' },
    { value: 'media', label: 'Média' },
    { value: 'alta', label: 'Alta' },
];

const statusOptions: { value: RequirementStatus; label: string }[] = [
    { value: 'pendente', label: 'Pendente' },
    { value: 'em_andamento', label: 'Em andamento' },
    { value: 'concluido', label: 'Concluído' },
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
                    <Label htmlFor={`${idPrefix}-code`}>Código</Label>
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
                    <Label htmlFor={`${idPrefix}-type`}>Tipo</Label>
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
                <Label htmlFor={`${idPrefix}-title`}>Título</Label>
                <Input
                    id={`${idPrefix}-title`}
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                />
                <InputError message={form.errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-description`}>Descrição</Label>
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
                    <Label htmlFor={`${idPrefix}-priority`}>Prioridade</Label>
                    <Select
                        value={form.data.priority || NONE}
                        onValueChange={(value) => form.setData('priority', value === NONE ? '' : value)}
                    >
                        <SelectTrigger id={`${idPrefix}-priority`} className="w-full">
                            <SelectValue placeholder="Sem prioridade" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>Sem prioridade</SelectItem>
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
                            <SelectValue placeholder="Sem status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>Sem status</SelectItem>
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
                        Cancelar
                    </Button>
                </DialogClose>
                <Button type="submit" disabled={form.processing}>
                    Salvar
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
            <Head title="Gerenciar requisitos" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Gerenciar requisitos"
                        description="Requisitos funcionais e não funcionais que podem ser vinculados a casos de teste."
                    />

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus /> Novo requisito
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Novo requisito</DialogTitle>
                            <DialogDescription>Cadastre um requisito funcional ou não funcional.</DialogDescription>
                            <RequirementForm form={createForm} idPrefix="create" onSubmit={submitCreate} />
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="overflow-hidden py-0">
                    {requirements.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">Nenhum requisito cadastrado ainda.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Código</th>
                                    <th className="px-4 py-3 font-medium">Título</th>
                                    <th className="px-4 py-3 font-medium">Tipo</th>
                                    <th className="px-4 py-3 font-medium">Casos vinculados</th>
                                    <th className="px-4 py-3 font-medium">Ações</th>
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
                <Badge variant="secondary">{requirement.type === 'funcional' ? 'RF' : 'RNF'}</Badge>
            </td>
            <td className="px-4 py-3">{requirement.test_cases_count}</td>
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <Dialog open={editOpen} onOpenChange={setEditOpen}>
                        <DialogTrigger asChild>
                            <Button variant="outline" size="sm">
                                Editar
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Editar requisito</DialogTitle>
                            <DialogDescription>Atualize os dados de &quot;{requirement.code}&quot;.</DialogDescription>
                            <RequirementForm form={editForm} idPrefix={`edit-${requirement.id}`} onSubmit={submitEdit} />
                        </DialogContent>
                    </Dialog>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive" size="sm">
                                Excluir
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Excluir requisito?</DialogTitle>
                            <DialogDescription>
                                Esta ação não pode ser desfeita. Casos de teste vinculados a &quot;{requirement.code}&quot; perderão
                                essa associação.
                            </DialogDescription>

                            <Form {...RequirementController.destroy.form(requirement)}>
                                {({ processing }) => (
                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button type="button" variant="secondary">
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button type="submit" variant="destructive" disabled={processing}>
                                            Excluir
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
    breadcrumbs: [{ title: 'Gerenciar requisitos', href: index() }],
};
