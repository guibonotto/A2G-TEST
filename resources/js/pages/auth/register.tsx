import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store as loginStore } from '@/routes/login';
import { store } from '@/routes/register';

const roles = [
    { id: 'role-qa', value: 'qa', name: 'QA / Tester', desc: 'Criar e editar testes' },
    { id: 'role-dev', value: 'developer', name: 'Desenvolvedor', desc: 'Visualizar e executar' },
    { id: 'role-admin', value: 'admin', name: 'Administrador', desc: 'Acesso total' },
    { id: 'role-viewer', value: 'viewer', name: 'Visualizador', desc: 'Somente leitura' },
];

const fieldClassName =
    'flex h-10 w-full rounded-lg border border-input bg-secondary px-3.5 font-mono text-sm text-foreground outline-none transition-colors placeholder:text-muted-foreground/60 focus:border-primary focus:ring-3 focus:ring-primary/15';

const labelClassName = 'font-mono text-xs tracking-wide text-muted-foreground uppercase';

export default function Register() {
    const [selectedRole, setSelectedRole] = useState('qa');

    return (
        <>
            <Head title="Criar conta" />

            <div className="bg-grid" />

            <div className="fade-in mx-auto flex min-h-screen w-full max-w-md flex-col justify-center gap-8 px-6 py-12">
                <Link href="/" className="flex items-center justify-center gap-2 font-mono text-lg font-bold tracking-wide">
                    <span className="size-1.5 animate-pulse rounded-full bg-info" />
                    A2G<span className="text-primary">&nbsp;TEST</span>
                    <span className="text-info">.</span>
                </Link>

                <Card>
                    <CardContent className="flex flex-col gap-6">
                        <div>
                            <h1 className="text-2xl font-extrabold tracking-tight">Crie sua conta</h1>
                            <p className="mt-1.5 font-mono text-xs text-muted-foreground">
                                Junte-se ao A2G TEST e gerencie seus testes com eficiência.
                            </p>
                        </div>

                        <Form {...store.form()} resetOnSuccess={['password', 'password_confirmation']} className="flex flex-col gap-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="first_name" className={labelClassName}>
                                                Nome
                                            </Label>
                                            <input
                                                id="first_name"
                                                className={fieldClassName}
                                                type="text"
                                                name="first_name"
                                                placeholder="João"
                                                autoFocus
                                                autoComplete="given-name"
                                            />
                                            <InputError message={errors.first_name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="last_name" className={labelClassName}>
                                                Sobrenome
                                            </Label>
                                            <input
                                                id="last_name"
                                                className={fieldClassName}
                                                type="text"
                                                name="last_name"
                                                placeholder="Silva"
                                                autoComplete="family-name"
                                            />
                                            <InputError message={errors.last_name} />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email" className={labelClassName}>
                                            E-mail
                                        </Label>
                                        <input
                                            id="email"
                                            className={fieldClassName}
                                            type="email"
                                            name="email"
                                            placeholder="joao@empresa.com"
                                            autoComplete="email"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password" className={labelClassName}>
                                            Senha
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                            name="password"
                                            placeholder="••••••••••"
                                            autoComplete="new-password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation" className={labelClassName}>
                                            Confirmar senha
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                            name="password_confirmation"
                                            placeholder="••••••••••"
                                            autoComplete="new-password"
                                        />
                                        <InputError message={errors.password_confirmation} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className={labelClassName}>Perfil de acesso</Label>
                                        <div className="grid grid-cols-2 gap-2.5">
                                            {roles.map((role) => (
                                                <div key={role.id}>
                                                    <input
                                                        type="radio"
                                                        name="role"
                                                        id={role.id}
                                                        value={role.value}
                                                        checked={selectedRole === role.value}
                                                        onChange={() => setSelectedRole(role.value)}
                                                        className="peer sr-only"
                                                    />
                                                    <label
                                                        htmlFor={role.id}
                                                        className={cn(
                                                            'flex cursor-pointer flex-col gap-1 rounded-lg border border-input bg-secondary px-4 py-3 transition-colors',
                                                            'peer-checked:border-primary peer-checked:bg-primary/8',
                                                            'hover:border-primary/40',
                                                        )}
                                                    >
                                                        <span className="text-sm font-bold">{role.name}</span>
                                                        <span className="font-mono text-[11px] text-muted-foreground">{role.desc}</span>
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                        <InputError message={errors.role} />
                                    </div>

                                    <Button type="submit" className="mt-1 w-full" disabled={processing}>
                                        {processing && <Spinner />}
                                        Criar conta →
                                    </Button>

                                    <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                        <span className="h-px flex-1 bg-border" />
                                        ou
                                        <span className="h-px flex-1 bg-border" />
                                    </div>

                                    <Button type="button" variant="outline" className="w-full">
                                        Continuar com Google
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                <p className="text-center font-mono text-xs text-muted-foreground">
                    Já tem uma conta?{' '}
                    <Link href={loginStore.url()} className="text-primary hover:underline">
                        Fazer login
                    </Link>
                </p>
            </div>
        </>
    );
}
