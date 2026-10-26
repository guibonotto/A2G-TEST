import { Head, router, usePage } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index, update } from '@/routes/accounts';
import type { Account, AssignableRole } from '@/types';

type Props = {
    accounts: Account[];
    roles: AssignableRole[];
};

type PendingChange = {
    account: Account;
    roleId: number | null;
    roleName: string;
};

export default function ManageAccounts({ accounts, roles }: Props) {
    const { auth } = usePage().props;
    const [pending, setPending] = useState<PendingChange | null>(null);
    const [saving, setSaving] = useState(false);

    function stageRoleChange(account: Account, value: string) {
        const roleId = value === 'none' ? null : Number(value);
        const roleName =
            roles.find((role) => role.id === roleId)?.name ?? 'No role';

        setPending({ account, roleId, roleName });
    }

    function confirmRoleChange() {
        if (!pending) {
            return;
        }

        setSaving(true);

        router.put(
            update.url(pending.account.id),
            { role_id: pending.roleId },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSaving(false);
                    setPending(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Manage accounts" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Manage accounts"
                    description="Set the role each member holds in the current project. Roles are per project, so the same person can have a different role elsewhere. You can only change users below your own role, and only to roles you outrank."
                />

                <Card className="overflow-hidden py-0">
                    {accounts.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            This project has no members yet.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Role
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {accounts.map((account) => {
                                    const isSelf = account.id === auth.user.id;

                                    return (
                                        <tr
                                            key={account.id}
                                            className="border-b last:border-0 hover:bg-muted/50"
                                        >
                                            <td className="px-4 py-3">
                                                {account.name}
                                                {isSelf && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        (you)
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {account.email}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    {account.can_change_role ? (
                                                        <Select
                                                            value={
                                                                account.role
                                                                    ? String(
                                                                          account
                                                                              .role
                                                                              .id,
                                                                      )
                                                                    : 'none'
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                stageRoleChange(
                                                                    account,
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger className="w-48">
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="none">
                                                                    No role
                                                                </SelectItem>
                                                                {roles.map(
                                                                    (role) => (
                                                                        <SelectItem
                                                                            key={
                                                                                role.id
                                                                            }
                                                                            value={String(
                                                                                role.id,
                                                                            )}
                                                                            disabled={
                                                                                !role.assignable
                                                                            }
                                                                        >
                                                                            {
                                                                                role.name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    ) : (
                                                        /* A plain grey name
                                                           looked like a
                                                           rendering glitch;
                                                           the lock says the
                                                           row is governed by
                                                           the hierarchy. */
                                                        <span
                                                            className="inline-flex h-9 w-48 items-center gap-1.5 text-muted-foreground"
                                                            title="Only someone whose role outranks this one can change it."
                                                        >
                                                            <Lock
                                                                className="size-3.5 shrink-0"
                                                                aria-hidden="true"
                                                            />
                                                            {account.role
                                                                ?.name ??
                                                                'No role'}
                                                            <span className="sr-only">
                                                                (you cannot
                                                                change this
                                                                role)
                                                            </span>
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </Card>
            </div>

            <Dialog
                open={pending !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPending(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogTitle>Change role?</DialogTitle>

                    <DialogDescription>
                        {pending && (
                            <>
                                {pending.account.name} goes from{' '}
                                <Badge variant="secondary">
                                    {pending.account.role?.name ?? 'No role'}
                                </Badge>{' '}
                                to <Badge>{pending.roleName}</Badge>. This
                                changes what they can access across the whole
                                system.
                            </>
                        )}
                    </DialogDescription>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            type="button"
                            onClick={confirmRoleChange}
                            disabled={saving}
                        >
                            Change role
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

ManageAccounts.layout = {
    breadcrumbs: [{ title: 'Manage accounts', href: index() }],
};
