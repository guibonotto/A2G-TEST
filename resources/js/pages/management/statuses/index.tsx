import { Form, Head, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index, store, update } from '@/routes/test-case-statuses';
import type { TestCaseStatus, TestCaseStatusColor } from '@/types';

type ManagedStatus = TestCaseStatus & { test_cases_count: number };

type Props = {
    statuses: ManagedStatus[];
};

const colorOptions: { value: TestCaseStatusColor; label: string }[] = [
    { value: 'success', label: 'Verde' },
    { value: 'destructive', label: 'Vermelho' },
    { value: 'warning', label: 'Âmbar' },
    { value: 'info', label: 'Teal' },
    { value: 'secondary', label: 'Cinza' },
];

export default function ManageTestCaseStatuses({ statuses }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const createForm = useForm({ name: '', color: 'secondary' as TestCaseStatusColor });

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
            <Head title="Gerenciar status" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Gerenciar status"
                        description="Adicione, edite ou remova os status disponíveis para os casos de teste."
                    />

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus /> Novo status
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Novo status</DialogTitle>
                            <DialogDescription>Defina o nome e a cor do novo status.</DialogDescription>

                            <form onSubmit={submitCreate} className="flex flex-col gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="create-name">Nome</Label>
                                    <Input
                                        id="create-name"
                                        value={createForm.data.name}
                                        onChange={(e) => createForm.setData('name', e.target.value)}
                                        autoFocus
                                    />
                                    <InputError message={createForm.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-color">Cor</Label>
                                    <Select
                                        value={createForm.data.color}
                                        onValueChange={(value) =>
                                            createForm.setData('color', value as TestCaseStatusColor)
                                        }
                                    >
                                        <SelectTrigger id="create-color" className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {colorOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={createForm.errors.color} />
                                </div>

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button type="button" variant="secondary">
                                            Cancelar
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={createForm.processing}>
                                        Criar status
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="overflow-hidden py-0">
                    {statuses.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">Nenhum status cadastrado ainda.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Cor</th>
                                    <th className="px-4 py-3 font-medium">Casos de teste</th>
                                    <th className="px-4 py-3 font-medium">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {statuses.map((status) => (
                                    <StatusRow key={status.id} status={status} />
                                ))}
                            </tbody>
                        </table>
                    )}
                </Card>
            </div>
        </>
    );
}

function StatusRow({ status }: { status: ManagedStatus }) {
    const [editOpen, setEditOpen] = useState(false);

    const editForm = useForm({ name: status.name, color: status.color });

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
                <Badge variant={status.color}>{status.name}</Badge>
            </td>
            <td className="px-4 py-3 text-muted-foreground">
                {colorOptions.find((option) => option.value === status.color)?.label ?? status.color}
            </td>
            <td className="px-4 py-3">{status.test_cases_count}</td>
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <Dialog open={editOpen} onOpenChange={setEditOpen}>
                        <DialogTrigger asChild>
                            <Button variant="outline" size="sm">
                                Editar
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Editar status</DialogTitle>
                            <DialogDescription>Atualize o nome e a cor de "{status.name}".</DialogDescription>

                            <form onSubmit={submitEdit} className="flex flex-col gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor={`edit-name-${status.id}`}>Nome</Label>
                                    <Input
                                        id={`edit-name-${status.id}`}
                                        value={editForm.data.name}
                                        onChange={(e) => editForm.setData('name', e.target.value)}
                                        autoFocus
                                    />
                                    <InputError message={editForm.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`edit-color-${status.id}`}>Cor</Label>
                                    <Select
                                        value={editForm.data.color}
                                        onValueChange={(value) =>
                                            editForm.setData('color', value as TestCaseStatusColor)
                                        }
                                    >
                                        <SelectTrigger id={`edit-color-${status.id}`} className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {colorOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={editForm.errors.color} />
                                </div>

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button type="button" variant="secondary">
                                            Cancelar
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={editForm.processing}>
                                        Salvar
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive" size="sm">
                                Excluir
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Excluir status?</DialogTitle>
                            <DialogDescription>
                                Esta ação não pode ser desfeita. Casos de teste com o status "{status.name}"
                                ficarão sem status atribuído.
                            </DialogDescription>

                            <Form {...TestCaseStatusController.destroy.form(status)}>
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

ManageTestCaseStatuses.layout = {
    breadcrumbs: [{ title: 'Gerenciar status', href: index() }],
};
