import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { index, update } from '@/routes/role-permissions';
import type { AvailablePermission, Role } from '@/types';

type Props = {
    roles: Role[];
    availablePermissions: AvailablePermission[];
};

export default function ManageRolePermissions({ roles, availablePermissions }: Props) {
    return (
        <>
            <Head title="Gerenciar permissões" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Gerenciar permissões"
                    description="Defina quais ações cada papel pode executar no sistema."
                />

                <div className="flex flex-col gap-4">
                    {roles.map((role) => (
                        <RoleCard key={role.id} role={role} availablePermissions={availablePermissions} />
                    ))}
                </div>
            </div>
        </>
    );
}

function RoleCard({ role, availablePermissions }: { role: Role; availablePermissions: AvailablePermission[] }) {
    const form = useForm({ permissions: role.permissions ?? [] });

    function togglePermission(value: string, checked: boolean) {
        form.setData(
            'permissions',
            checked
                ? [...form.data.permissions, value]
                : form.data.permissions.filter((permission) => permission !== value),
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        form.put(update.url(role.id), { preserveScroll: true });
    }

    return (
        <Card className="p-6">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div>
                    <h3 className="font-medium">{role.name}</h3>
                    <p className="text-sm text-muted-foreground">{role.slug}</p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    {availablePermissions.map((permission) => (
                        <div key={permission.value} className="flex items-center gap-2">
                            <Checkbox
                                id={`${role.id}-${permission.value}`}
                                checked={form.data.permissions.includes(permission.value)}
                                onCheckedChange={(checked) => togglePermission(permission.value, checked === true)}
                            />
                            <Label htmlFor={`${role.id}-${permission.value}`} className="font-normal">
                                {permission.label}
                            </Label>
                        </div>
                    ))}
                </div>

                <div>
                    <Button type="submit" size="sm" disabled={form.processing}>
                        Salvar
                    </Button>
                </div>
            </form>
        </Card>
    );
}

ManageRolePermissions.layout = {
    breadcrumbs: [{ title: 'Gerenciar permissões', href: index() }],
};
