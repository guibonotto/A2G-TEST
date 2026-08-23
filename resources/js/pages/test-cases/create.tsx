import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { index, store } from '@/routes/test-cases';
import type { Classification, TestCaseStatus, TestTemplate } from '@/types';

type Props = {
    classifications: Classification[];
    templates: TestTemplate[];
    statuses: TestCaseStatus[];
};

type StepForm = {
    description: string;
    expected_result: string;
};

const emptyStep: StepForm = { description: '', expected_result: '' };

export default function CreateTestCase({ classifications, templates, statuses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        classification_id: '',
        template_id: '',
        status: 'PENDENTE' as TestCaseStatus,
        steps: [{ ...emptyStep }] as StepForm[],
    });

    const stepErrors = errors as Record<string, string | undefined>;

    function updateStep(stepIndex: number, field: keyof StepForm, value: string) {
        const steps = data.steps.map((step, i) =>
            i === stepIndex ? { ...step, [field]: value } : step,
        );
        setData('steps', steps);
    }

    function addStep() {
        setData('steps', [...data.steps, { ...emptyStep }]);
    }

    function removeStep(stepIndex: number) {
        setData(
            'steps',
            data.steps.filter((_, i) => i !== stepIndex),
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        post(store.url());
    }

    return (
        <>
            <Head title="Criar caso de teste" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Criar caso de teste"
                    description="Defina os dados gerais e os passos de execução do caso de teste."
                />

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Título</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    autoFocus
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Descrição</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="classification_id">Classificação</Label>
                                    <Select
                                        value={data.classification_id}
                                        onValueChange={(value) => setData('classification_id', value)}
                                    >
                                        <SelectTrigger id="classification_id" className="w-full">
                                            <SelectValue placeholder="Selecione uma classificação" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {classifications.map((classification) => (
                                                <SelectItem
                                                    key={classification.id}
                                                    value={String(classification.id)}
                                                >
                                                    {classification.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.classification_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="template_id">Template (opcional)</Label>
                                    <Select
                                        value={data.template_id}
                                        onValueChange={(value) => setData('template_id', value)}
                                    >
                                        <SelectTrigger id="template_id" className="w-full">
                                            <SelectValue placeholder="Nenhum template" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {templates.map((template) => (
                                                <SelectItem key={template.id} value={String(template.id)}>
                                                    {template.title}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.template_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData('status', value as TestCaseStatus)}
                                    >
                                        <SelectTrigger id="status" className="w-full">
                                            <SelectValue placeholder="Selecione um status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((status) => (
                                                <SelectItem key={status} value={status}>
                                                    {status}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="flex items-center justify-between">
                                <Heading variant="small" title="Passos" description="Adicione ao menos um passo." />
                                <Button type="button" variant="outline" size="sm" onClick={addStep}>
                                    <Plus /> Adicionar passo
                                </Button>
                            </div>

                            <InputError message={errors.steps} />

                            {data.steps.map((step, stepIndex) => (
                                <div key={stepIndex} className="flex flex-col gap-3 rounded-lg border p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Passo {stepIndex + 1}</span>
                                        {data.steps.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeStep(stepIndex)}
                                            >
                                                <Trash2 />
                                            </Button>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor={`step-description-${stepIndex}`}>Ação</Label>
                                        <Textarea
                                            id={`step-description-${stepIndex}`}
                                            value={step.description}
                                            onChange={(e) => updateStep(stepIndex, 'description', e.target.value)}
                                        />
                                        <InputError message={stepErrors[`steps.${stepIndex}.description`]} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor={`step-expected-${stepIndex}`}>Resultado esperado</Label>
                                        <Textarea
                                            id={`step-expected-${stepIndex}`}
                                            value={step.expected_result}
                                            onChange={(e) =>
                                                updateStep(stepIndex, 'expected_result', e.target.value)
                                            }
                                        />
                                        <InputError message={stepErrors[`steps.${stepIndex}.expected_result`]} />
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            Criar caso de teste
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

CreateTestCase.layout = {
    breadcrumbs: [
        { title: 'Casos de teste', href: index() },
        { title: 'Criar', href: '#' },
    ],
};
