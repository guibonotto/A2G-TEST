import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { store as registerStore } from '@/routes/register';

const demoAccounts = [
    { role: 'Administrador', email: 'admin@a2gtest.com' },
    { role: 'QA', email: 'qa@a2gtest.com' },
    { role: 'Visualizador', email: 'viewer@a2gtest.com' },
];

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Entrar" />

            <div className="bg-grid" />

            <main
                className="fade-in mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12"
                aria-labelledby="login-title"
            >
                {/* Logo */}
                <Link
                    href="/"
                    aria-label="A2G TEST - Página inicial"
                    className="mb-8 flex items-center justify-center gap-2 font-mono text-lg font-bold tracking-wide"
                >
                    <span
                        className="size-1.5 animate-pulse rounded-full bg-info"
                        aria-hidden="true"
                    />
                    A2G<span className="text-primary">&nbsp;TEST</span>
                    <span className="text-info" aria-hidden="true">
                        .
                    </span>
                </Link>

                <Card>
                    <CardContent className="flex flex-col gap-6">
                        {/* Header */}
                        <header>
                            <h1
                                id="login-title"
                                className="text-2xl font-extrabold tracking-tight"
                            >
                                Entrar
                            </h1>

                            <p className="mt-1.5 font-mono text-xs leading-relaxed text-muted-foreground">
                                Entre na sua conta para gerenciar seus casos de
                                teste.
                            </p>
                        </header>

                        {/* Success / status feedback */}
                        {status && (
                            <Alert
                                role="status"
                                className="border-success/25 bg-success/10 text-success"
                            >
                                <AlertDescription className="text-success">
                                    {status}
                                </AlertDescription>
                            </Alert>
                        )}

                        {/* Login form */}
                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Email */}
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className="font-mono text-xs tracking-wide text-muted-foreground uppercase"
                                        >
                                            E-mail
                                        </Label>

                                        <input
                                            id="email"
                                            className="flex h-11 w-full rounded-lg border border-input bg-secondary px-3.5 font-mono text-sm text-foreground outline-none transition-colors placeholder:text-muted-foreground/60 focus:border-primary focus:ring-3 focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-60"
                                            type="email"
                                            name="email"
                                            placeholder="seu@email.com"
                                            autoFocus
                                            autoComplete="email"
                                            required
                                            aria-invalid={
                                                errors.email
                                                    ? 'true'
                                                    : 'false'
                                            }
                                            aria-describedby={
                                                errors.email
                                                    ? 'email-error'
                                                    : undefined
                                            }
                                            disabled={processing}
                                        />

                                        {errors.email && (
                                            <div id="email-error">
                                                <InputError
                                                    message={errors.email}
                                                />
                                            </div>
                                        )}
                                    </div>

                                    {/* Password */}
                                    <div className="grid gap-2">
                                        <div className="flex items-center justify-between gap-4">
                                            <Label
                                                htmlFor="password"
                                                className="font-mono text-xs tracking-wide text-muted-foreground uppercase"
                                            >
                                                Senha
                                            </Label>

                                            {canResetPassword && (
                                                <Link
                                                    href={request()}
                                                    className="font-mono text-xs text-muted-foreground transition-colors hover:text-primary hover:underline"
                                                >
                                                    Esqueceu a senha?
                                                </Link>
                                            )}
                                        </div>

                                        <PasswordInput
                                            id="password"
                                            className="h-11 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                            name="password"
                                            placeholder="Digite sua senha"
                                            autoComplete="current-password"
                                            required
                                            aria-invalid={
                                                errors.password
                                                    ? 'true'
                                                    : 'false'
                                            }
                                            aria-describedby={
                                                errors.password
                                                    ? 'password-error'
                                                    : undefined
                                            }
                                            disabled={processing}
                                        />

                                        {errors.password && (
                                            <div id="password-error">
                                                <InputError
                                                    message={errors.password}
                                                />
                                            </div>
                                        )}
                                    </div>

                                    {/* Submit */}
                                    <Button
                                        type="submit"
                                        className="mt-1 h-11 w-full"
                                        disabled={processing}
                                        aria-busy={processing}
                                    >
                                        {processing ? (
                                            <>
                                                <Spinner />
                                                Entrando...
                                            </>
                                        ) : (
                                            'Entrar →'
                                        )}
                                    </Button>
                                </>
                            )}
                        </Form>

                        {/* Demo accounts */}
                        <details className="group rounded-lg border border-border/60 bg-secondary/40">
                            <summary className="cursor-pointer list-none px-4 py-3 font-mono text-[10px] tracking-widest text-muted-foreground uppercase transition-colors hover:text-foreground">
                                <span className="group-open:text-primary">
                                    // Contas de demonstração
                                </span>
                            </summary>

                            <div className="border-t border-border/60 px-4 py-3">
                                <p className="mb-3 text-xs leading-relaxed text-muted-foreground">
                                    Utilize uma destas contas para testar as
                                    funcionalidades do sistema.
                                </p>

                                <div className="flex flex-col gap-2">
                                    {demoAccounts.map((account) => (
                                        <div
                                            key={account.role}
                                            className="flex flex-wrap items-center gap-2 rounded-md border border-border/50 bg-background/40 px-3 py-2"
                                        >
                                            <Badge
                                                variant="info"
                                                className="shrink-0 uppercase"
                                            >
                                                {account.role}
                                            </Badge>

                                            <span className="whitespace-nowrap font-mono text-xs text-muted-foreground">
                                                {account.email}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </details>
                    </CardContent>
                </Card>

                {/* Registration */}
                <p className="mt-6 text-center font-mono text-xs text-muted-foreground">
                    Ainda não possui uma conta?{' '}
                    <Link
                        href={registerStore.url()}
                        className="font-medium text-primary hover:underline"
                    >
                        Criar conta
                    </Link>
                </p>
            </main>
        </>
    );
}