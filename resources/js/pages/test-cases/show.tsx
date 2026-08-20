import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index, show } from '@/routes/test-cases';
import type { TestCaseDetail } from '@/types';

type Props = {
    testCase: TestCaseDetail;
};

export default function ShowTestCase({ testCase }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Casos de teste', href: index() },
            { title: testCase.title, href: show(testCase.id) },
        ],
    });

    return (
        <>
            <Head title={testCase.title} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Button asChild variant="ghost" size="sm" className="w-fit -ml-2">
                            <Link href={index()}>
                                <ArrowLeft /> Voltar para casos de teste
                            </Link>
                        </Button>
                        <Heading title={testCase.title} description={testCase.description ?? undefined} />
                    </div>

                    {testCase.classification && (
                        <Badge variant="secondary">{testCase.classification.name}</Badge>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Informações gerais
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <div className="text-muted-foreground">Classificação</div>
                            <div>{testCase.classification?.name ?? '—'}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Template</div>
                            <div>{testCase.template?.title ?? '—'}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Criado por</div>
                            <div>{testCase.creator?.name ?? '—'}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Passos ({testCase.steps.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {testCase.steps.map((step) => (
                            <div key={step.id} className="flex flex-col gap-2 rounded-lg border p-4">
                                <span className="text-sm font-medium">Passo {step.order}</span>

                                <div>
                                    <div className="text-xs text-muted-foreground">Ação</div>
                                    <p className="text-sm">{step.description}</p>
                                </div>

                                {step.expected_result && (
                                    <div>
                                        <div className="text-xs text-muted-foreground">Resultado esperado</div>
                                        <p className="text-sm">{step.expected_result}</p>
                                    </div>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
