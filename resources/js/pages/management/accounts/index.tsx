import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index, update } from '@/routes/accounts';
import type { Role, User } from '@/types';

type Props = {
    accounts: User[];
    roles: Role[];
};

export default function ManageAccounts({ accounts, roles }: Props) {
    function handleRoleChange(userId: number, value: string) {
        router.put(
            update.url(userId),
            { role_id: value === 'none' ? null : Number(value) },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Gerenciar contas" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Gerenciar contas"
                    description="Defina o papel (role) de cada usuário cadastrado no sistema."
                />

                <Card className="overflow-hidden py-0">
                    {accounts.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">Nenhuma conta cadastrada ainda.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Nome</th>
                                    <th className="px-4 py-3 font-medium">E-mail</th>
                                    <th className="px-4 py-3 font-medium">Papel</th>
                                </tr>
                            </thead>
                            <tbody>
                                {accounts.map((account) => (
                                    <tr key={account.id} className="border-b last:border-0 hover:bg-muted/50">
                                        <td className="px-4 py-3">{account.name}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{account.email}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <Select
                                                    value={account.role ? String(account.role.id) : 'none'}
                                                    onValueChange={(value) => handleRoleChange(account.id, value)}
                                                >
                                                    <SelectTrigger className="w-48">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none">Sem papel</SelectItem>
                                                        {roles.map((role) => (
                                                            <SelectItem key={role.id} value={String(role.id)}>
                                                                {role.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {account.role && <Badge variant="secondary">{account.role.slug}</Badge>}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </Card>
            </div>
        </>
    );
}

ManageAccounts.layout = {
    breadcrumbs: [{ title: 'Gerenciar contas', href: index() }],
};
