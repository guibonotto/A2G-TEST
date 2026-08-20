import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { create, index, show } from '@/routes/test-cases';
import type { TestCaseListItem } from '@/types';

type Props = {
    testCases: TestCaseListItem[];
};

export default function TestCaseIndex({ testCases }: Props) {
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

                <Card className="overflow-hidden py-0">
                    {testCases.length === 0 ? (
                        <p className="p-6 text-sm text-muted-foreground">
                            Nenhum caso de teste cadastrado ainda.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Título</th>
                                    <th className="px-4 py-3 font-medium">Classificação</th>
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
