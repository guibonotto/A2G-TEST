import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppBrand from '@/components/app-brand';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const demoAccounts = [
    { role: 'Test Manager', email: 'admin@a2gtest.com' },
    { role: 'QA', email: 'qa@a2gtest.com' },
    { role: 'Viewer', email: 'viewer@a2gtest.com' },
];

/**
 * Demo credentials are a development aid, so they are stripped from the
 * production bundle instead of being advertised on the public login page.
 */
const showDemoAccounts = import.meta.env.DEV;

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            <div className="bg-grid" />

            <main
                className="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center gap-6 px-6 py-12 fade-in"
                aria-labelledby="login-title"
            >
                <div className="flex items-center justify-between gap-4">
                    <Link
                        href={home()}
                        aria-label="A2G TEST - Home"
                        className="rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <AppBrand />
                    </Link>

                    <Link
                        href={home()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-foreground hover:underline"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Back to site
                    </Link>
                </div>

                <Card>
                    <CardContent className="flex flex-col gap-6">
                        <header>
                            <h1
                                id="login-title"
                                className="text-2xl font-extrabold tracking-tight"
                            >
                                Log in
                            </h1>
                        </header>

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

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>

                                        <Input
                                            id="email"
                                            className="h-11 rounded-lg bg-secondary text-sm"
                                            type="email"
                                            name="email"
                                            placeholder="you@example.com"
                                            autoFocus
                                            autoComplete="email"
                                            required
                                            aria-invalid={
                                                errors.email ? 'true' : 'false'
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

                                    <div className="grid gap-2">
                                        <div className="flex items-center justify-between gap-4">
                                            <Label htmlFor="password">
                                                Password
                                            </Label>

                                            {canResetPassword && (
                                                <Link
                                                    href={request()}
                                                    className="text-sm font-medium text-primary underline-offset-4 transition-colors hover:underline"
                                                >
                                                    Forgot password?
                                                </Link>
                                            )}
                                        </div>

                                        <PasswordInput
                                            id="password"
                                            className="h-11 rounded-lg bg-secondary text-sm"
                                            name="password"
                                            placeholder="Enter your password"
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

                                    <Button
                                        type="submit"
                                        className="mt-1 h-11 w-full"
                                        disabled={processing}
                                        aria-busy={processing}
                                    >
                                        {processing ? (
                                            <>
                                                <Spinner />
                                                Logging in...
                                            </>
                                        ) : (
                                            'Log in'
                                        )}
                                    </Button>
                                </>
                            )}
                        </Form>

                        <p className="text-center text-sm text-muted-foreground">
                            Don&apos;t have an account yet?{' '}
                            <Link
                                href={register()}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Create account
                            </Link>
                        </p>

                        {showDemoAccounts && (
                            <details className="rounded-lg border border-border/60 bg-secondary/40">
                                <summary className="cursor-pointer list-none px-4 py-3 text-sm font-medium transition-colors hover:text-primary">
                                    Try with a demo account
                                </summary>

                                <div className="border-t border-border/60 px-4 py-3">
                                    <p className="mb-3 text-sm leading-relaxed text-muted-foreground">
                                        Development only. Use one of these
                                        accounts to try out the system features.
                                    </p>

                                    <div className="flex flex-col gap-2">
                                        {demoAccounts.map((account) => (
                                            <div
                                                key={account.role}
                                                className="flex flex-wrap items-center gap-2 rounded-md border border-border/50 bg-background/40 px-3 py-2"
                                            >
                                                <Badge
                                                    variant="info"
                                                    className="shrink-0"
                                                >
                                                    {account.role}
                                                </Badge>

                                                <span className="text-sm whitespace-nowrap text-muted-foreground">
                                                    {account.email}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </details>
                        )}
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
