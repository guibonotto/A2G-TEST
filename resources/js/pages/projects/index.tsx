import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index as projectsIndex } from '@/routes/projects';

type Mode = 'create' | 'join';

export default function ProjectsIndex() {
    const [mode, setMode] = useState<Mode>('create');

    return (
        <>
            <Head title="Projetos" />

            <div className="mx-auto w-full max-w-lg space-y-6">
                <Heading
                    title="Projetos"
                    description="Crie um novo projeto ou entre em um projeto existente informando o ID e a senha."
                />

                <div className="inline-flex rounded-lg border p-1">
                    <button
                        type="button"
                        onClick={() => setMode('create')}
                        className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${
                            mode === 'create'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Criar projeto
                    </button>
                    <button
                        type="button"
                        onClick={() => setMode('join')}
                        className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${
                            mode === 'join'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Entrar em um projeto
                    </button>
                </div>

                {mode === 'create' ? (
                    <Form
                        {...ProjectController.store.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        Nome do projeto
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Ex: Projeto Alpha"
                                        autoFocus
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        Senha do projeto
                                    </Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        placeholder="••••••••••"
                                        autoComplete="new-password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar senha
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        placeholder="••••••••••"
                                        autoComplete="new-password"
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Criar projeto
                                </Button>
                            </>
                        )}
                    </Form>
                ) : (
                    <Form
                        {...ProjectController.join.form()}
                        resetOnSuccess={['password']}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="uuid">ID do projeto</Label>
                                    <Input
                                        id="uuid"
                                        name="uuid"
                                        placeholder="Cole aqui o ID compartilhado com você"
                                        autoFocus
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.uuid} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="join-password">
                                        Senha do projeto
                                    </Label>
                                    <PasswordInput
                                        id="join-password"
                                        name="password"
                                        placeholder="••••••••••"
                                        autoComplete="current-password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Entrar no projeto
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Projetos',
            href: projectsIndex(),
        },
    ],
};
