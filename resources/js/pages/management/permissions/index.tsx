import { Head, useForm } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/role-permissions';
import type { AvailablePermission, EditableRole } from '@/types';

type Props = {
    roles: EditableRole[];
    availablePermissions: AvailablePermission[];
};

export default function ManageRolePermissions({
    roles,
    availablePermissions,
}: Props) {
    return (
        <>
            <Head title="Manage permissions" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Manage permissions"
                    description="Set which actions each role can perform in the current project; other projects are not affected. You can only edit roles below your own; the administrator role always has every permission."
                />

                <div className="flex flex-col gap-4">
                    {roles.map((role) => (
                        <RoleCard
                            key={role.id}
                            role={role}
                            availablePermissions={availablePermissions}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

/** Permissions that hand out control of the system itself. */
const ADMIN_PERMISSIONS = ['users.manage', 'roles.manage', 'statuses.manage'];

function RoleCard({
    role,
    availablePermissions,
}: {
    role: EditableRole;
    availablePermissions: AvailablePermission[];
}) {
    const form = useForm({ permissions: role.effective_permissions });
    const isAdmin = role.slug === 'admin';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const saved = role.permissions ?? [];

    const isDirty =
        form.data.permissions.length !== saved.length ||
        form.data.permissions.some((permission) => !saved.includes(permission));

    /** Administrative permissions this save would newly grant. */
    const grantedAdmin = form.data.permissions.filter(
        (permission) =>
            ADMIN_PERMISSIONS.includes(permission) &&
            !saved.includes(permission),
    );

    function togglePermission(value: string, checked: boolean) {
        form.setData(
            'permissions',
            checked
                ? [...form.data.permissions, value]
                : form.data.permissions.filter(
                      (permission) => permission !== value,
                  ),
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        if (!role.can_edit) {
            return;
        }

        if (grantedAdmin.length > 0) {
            setConfirmOpen(true);

            return;
        }

        save();
    }

    function save() {
        form.put(update.url(role.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmOpen(false),
        });
    }

    return (
        <Card className="p-6">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h3 className="font-medium">{role.name}</h3>
                        <p className="text-sm text-muted-foreground">
                            {role.slug}
                        </p>
                    </div>
                    {!role.can_edit && (
                        <Badge variant="secondary" className="gap-1">
                            <Lock className="size-3" />
                            {isAdmin
                                ? 'Always full access'
                                : 'Not editable by you'}
                        </Badge>
                    )}

                    {role.can_edit && isDirty && (
                        <span className="text-sm text-warning">
                            Unsaved changes
                        </span>
                    )}
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    {availablePermissions.map((permission) => (
                        <div
                            key={permission.value}
                            className="flex items-center gap-2"
                        >
                            <Checkbox
                                id={`${role.id}-${permission.value}`}
                                checked={
                                    isAdmin ||
                                    form.data.permissions.includes(
                                        permission.value,
                                    )
                                }
                                disabled={!role.can_edit}
                                onCheckedChange={(checked) =>
                                    togglePermission(
                                        permission.value,
                                        checked === true,
                                    )
                                }
                            />
                            <Label
                                htmlFor={`${role.id}-${permission.value}`}
                                className={
                                    role.can_edit
                                        ? 'font-normal'
                                        : 'font-normal text-muted-foreground'
                                }
                            >
                                {permission.label}
                            </Label>
                        </div>
                    ))}
                </div>

                {role.can_edit && (
                    <div>
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing || !isDirty}
                        >
                            Save
                        </Button>
                    </div>
                )}
            </form>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogTitle>Grant administrative access?</DialogTitle>

                    <DialogDescription>
                        {role.name} will be able to:{' '}
                        {grantedAdmin
                            .map(
                                (permission) =>
                                    availablePermissions.find(
                                        (available) =>
                                            available.value === permission,
                                    )?.label ?? permission,
                            )
                            .join(', ')}
                        . Everyone with this role is affected.
                    </DialogDescription>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            type="button"
                            onClick={save}
                            disabled={form.processing}
                        >
                            Grant access
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

ManageRolePermissions.layout = {
    breadcrumbs: [{ title: 'Manage permissions', href: index() }],
};
