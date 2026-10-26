import { Head, useForm } from '@inertiajs/react';
import { Check, Lock, X } from 'lucide-react';
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
    /** Roles from most to least access, as the editing rule sees them. */
    hierarchy: string[];
    roles: EditableRole[];
    availablePermissions: AvailablePermission[];
};

export default function ManageRolePermissions({
    roles,
    availablePermissions,
    hierarchy,
}: Props) {
    return (
        <>
            <Head title="Manage permissions" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Manage permissions"
                    description="Set which actions each role can perform in the current project; other projects are not affected. You can only edit roles below your own; the administrator role always has every permission."
                />

                {/* The rule says "only roles below yours", so the order has
                    to be on screen; alphabetical cards implied the opposite. */}
                <p className="text-sm text-muted-foreground">
                    Hierarchy, from most to least access:{' '}
                    {hierarchy.map((name, position) => (
                        <span key={name}>
                            {position > 0 && ' › '}
                            <span className="font-medium text-foreground">
                                {name}
                            </span>
                        </span>
                    ))}
                </p>

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
    const areas = [
        ...new Set(availablePermissions.map((permission) => permission.area)),
    ];

    const administrative = new Set(
        availablePermissions
            .filter((permission) => permission.administrative)
            .map((permission) => permission.value),
    );

    const grantedAdmin = form.data.permissions.filter(
        (permission) =>
            administrative.has(permission) && !saved.includes(permission),
    );

    /**
     * Some permissions are meaningless on their own: managing roles without
     * seeing the accounts they apply to, for instance. Granting one pulls in
     * what it needs, and revoking one drops whatever depended on it.
     */
    function togglePermission(value: string, checked: boolean) {
        const current = new Set(form.data.permissions);

        if (checked) {
            current.add(value);

            const required =
                availablePermissions.find(
                    (permission) => permission.value === value,
                )?.requires ?? [];

            required.forEach((permission) => current.add(permission));
        } else {
            current.delete(value);

            availablePermissions
                .filter((permission) => permission.requires.includes(value))
                .forEach((permission) => current.delete(permission.value));
        }

        form.setData('permissions', [...current]);
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

                    {role.read_only && (
                        <Badge variant="secondary" className="gap-1">
                            <Lock className="size-3" />
                            Read-only role
                        </Badge>
                    )}

                    {role.can_edit && !role.read_only && isDirty && (
                        <span className="text-sm text-warning">
                            Unsaved changes
                        </span>
                    )}
                </div>

                {/* A card the viewer cannot edit is a statement of fact, so
                    it reads as a list with ticks and crosses. Disabled
                    checkboxes rendered so pale that the two cards people most
                    need to understand were the hardest to read. */}
                {role.can_edit && !role.read_only ? (
                    <div className="flex flex-col gap-4">
                        {areas.map((area) => (
                            <div key={area} className="flex flex-col gap-2">
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {area}
                                </p>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    {availablePermissions
                                        .filter(
                                            (permission) =>
                                                permission.area === area,
                                        )
                                        .map((permission) => (
                                            <div
                                                key={permission.value}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`${role.id}-${permission.value}`}
                                                    checked={form.data.permissions.includes(
                                                        permission.value,
                                                    )}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        togglePermission(
                                                            permission.value,
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`${role.id}-${permission.value}`}
                                                    className="font-normal"
                                                >
                                                    {permission.label}
                                                </Label>
                                            </div>
                                        ))}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        {areas.map((area) => (
                            <div key={area} className="flex flex-col gap-2">
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {area}
                                </p>

                                <ul className="grid gap-2 sm:grid-cols-2">
                                    {availablePermissions
                                        .filter(
                                            (permission) =>
                                                permission.area === area,
                                        )
                                        .map((permission) => {
                                            const granted =
                                                isAdmin ||
                                                role.effective_permissions.includes(
                                                    permission.value,
                                                );

                                            return (
                                                <li
                                                    key={permission.value}
                                                    className="flex items-center gap-2 text-sm"
                                                >
                                                    {granted ? (
                                                        <Check
                                                            className="size-4 shrink-0 text-success"
                                                            aria-hidden="true"
                                                        />
                                                    ) : (
                                                        <X
                                                            className="size-4 shrink-0 text-muted-foreground"
                                                            aria-hidden="true"
                                                        />
                                                    )}

                                                    <span>
                                                        {permission.label}
                                                    </span>

                                                    <span className="sr-only">
                                                        {granted
                                                            ? '(granted)'
                                                            : '(not granted)'}
                                                    </span>
                                                </li>
                                            );
                                        })}
                                </ul>
                            </div>
                        ))}
                    </div>
                )}

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
