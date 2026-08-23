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
    { role: 'admin', email: 'admin@a2gtest.com' },
    { role: 'qa', email: 'qa@a2gtest.com' },
    { role: 'viewer', email: 'viewer@a2gtest.com' },
];

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Login" />

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
                            <h1 className="text-2xl font-extrabold tracking-tight">Bem-vindo de volta</h1>
                            <p className="mt-1.5 font-mono text-xs text-muted-foreground">
                                Acesse sua conta para gerenciar seus casos de teste.
                            </p>
                        </div>

                        {status && (
                            <Alert className="border-success/25 bg-success/10 text-success">
                                <AlertDescription className="text-success">{status}</AlertDescription>
                            </Alert>
                        )}

                        <div className="rounded-lg border border-primary/15 bg-primary/5 px-4 py-3.5">
                            <div className="mb-2.5 font-mono text-[10px] tracking-widest text-primary uppercase">
                                // Contas de demonstração
                            </div>
                            <div className="flex flex-col gap-1.5">
                                {demoAccounts.map((account) => (
                                    <div key={account.role} className="flex items-center gap-2 font-mono text-xs text-muted-foreground">
                                        <Badge variant="info" className="shrink-0 uppercase">
                                            {account.role}
                                        </Badge>
                                        {account.email}
                                    </div>
                                ))}
                            </div>
                        </div>

                        <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email" className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                            E-mail
                                        </Label>
                                        <input
                                            id="email"
                                            className="flex h-10 w-full rounded-lg border border-input bg-secondary px-3.5 font-mono text-sm text-foreground outline-none transition-colors placeholder:text-muted-foreground/60 focus:border-primary focus:ring-3 focus:ring-primary/15"
                                            type="email"
                                            name="email"
                                            placeholder="seu@email.com"
                                            autoFocus
                                            autoComplete="email"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <div className="flex items-center justify-between">
                                            <Label htmlFor="password" className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                                Senha
                                            </Label>
                                            {canResetPassword && (
                                                <Link href={request()} className="font-mono text-xs text-muted-foreground hover:text-foreground">
                                                    Esqueceu a senha?
                                                </Link>
                                            )}
                                        </div>
                                        <PasswordInput
                                            id="password"
                                            className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                            name="password"
                                            placeholder="••••••••••"
                                            autoComplete="current-password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <Button type="submit" className="mt-1 w-full" disabled={processing}>
                                        {processing && <Spinner />}
                                        Entrar →
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                <p className="text-center font-mono text-xs text-muted-foreground">
                    Não tem uma conta?{' '}
                    <Link href={registerStore.url()} className="text-primary hover:underline">
                        Criar conta grátis
                    </Link>
                </p>
            </div>
        </>
    );
}
