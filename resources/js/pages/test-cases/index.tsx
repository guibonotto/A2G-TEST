import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { testCaseStatusBadgeVariant } from '@/lib/test-case-status';
import { create, index, show } from '@/routes/test-cases';
import type { Classification, TestCaseFilters, TestCaseListItem, TestCaseStatus } from '@/types';

type Props = {
    testCases: TestCaseListItem[];
    classifications: Classification[];
    statuses: TestCaseStatus[];
    filters: TestCaseFilters;
};

export default function TestCaseIndex({ testCases, classifications, statuses, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                index.url(),
                { search, classification_id: filters.classification_id, status: filters.status },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function handleClassificationChange(value: string) {
        router.get(
            index.url(),
            { search, classification_id: value === 'all' ? null : Number(value), status: filters.status },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }

    function handleStatusChange(value: string) {
        router.get(
            index.url(),
            {
                search,
                classification_id: filters.classification_id,
                status: value === 'all' ? null : value,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Casos de teste" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Casos de teste"
                        description="Todos os casos de teste cadastrados."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Criar caso de teste
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar por nome ou ID..."
                        className="sm:max-w-xs"
                    />
                    <Select
                        value={filters.classification_id ? String(filters.classification_id) : 'all'}
                        onValueChange={handleClassificationChange}
                    >
                        <SelectTrigger className="sm:w-52">
                            <SelectValue placeholder="Tipo de caso de teste" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos os tipos</SelectItem>
                            {classifications.map((classification) => (
                                <SelectItem key={classification.id} value={String(classification.id)}>
                                    {classification.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={handleStatusChange}
                    >
                        <SelectTrigger className="sm:w-52">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos os status</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {status}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card className="overflow-hidden py-0">
                    {testCases.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            Nenhum caso de teste encontrado.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Título</th>
                                    <th className="px-4 py-3 font-medium">Classificação</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Passos</th>
                                    <th className="px-4 py-3 font-medium">Criado por</th>
                                    <th className="px-4 py-3 font-medium">Criado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                {testCases.map((testCase) => (
                                    <tr
                                        key={testCase.id}
                                        className="border-b last:border-0 hover:bg-muted/50"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                href={show(testCase.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {testCase.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {testCase.classification?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={testCaseStatusBadgeVariant(testCase.status)}>
                                                {testCase.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">{testCase.steps_count}</td>
                                        <td className="px-4 py-3">{testCase.creator?.name ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            {new Date(testCase.created_at).toLocaleDateString('pt-BR')}
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

TestCaseIndex.layout = {
    breadcrumbs: [{ title: 'Casos de teste', href: index() }],
};