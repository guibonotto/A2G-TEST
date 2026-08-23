import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

const labelClassName =
    'font-mono text-xs tracking-wide text-muted-foreground uppercase';

export default function ResetPassword({
    token,
    email,
    passwordRules,
}: Props) {
    return (
        <>
            <Head title="Redefinir senha" />

            <div className="bg-grid" />

            <div className="fade-in mx-auto flex min-h-screen w-full max-w-md flex-col justify-center gap-8 px-6 py-12">
                {/* Logo */}
                <div className="flex items-center justify-center gap-2 font-mono text-lg font-bold tracking-wide">
                    <span className="size-1.5 animate-pulse rounded-full bg-info" />
                    A2G
                    <span className="text-primary">&nbsp;TEST</span>
                    <span className="text-info">.</span>
                </div>

                <div className="rounded-xl border border-border bg-card p-6 shadow-sm">
                    <div className="flex flex-col gap-6">
                        {/* Header */}
                        <div>
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                Redefinir senha
                            </h1>

                            <p className="mt-1.5 font-mono text-xs leading-relaxed text-muted-foreground">
                                Defina uma nova senha para recuperar o acesso à
                                sua conta.
                            </p>
                        </div>

                        <Form
                            {...update.form()}
                            transform={(data) => ({
                                ...data,
                                token,
                                email,
                            })}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* E-mail */}
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className={labelClassName}
                                        >
                                            E-mail
                                        </Label>

                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            autoComplete="email"
                                            value={email}
                                            className="h-10 bg-secondary font-mono text-sm"
                                            readOnly
                                        />

                                        <InputError
                                            message={errors.email}
                                        />
                                    </div>

                                    {/* Nova senha */}
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className={labelClassName}
                                        >
                                            Nova senha
                                        </Label>

                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            autoComplete="new-password"
                                            className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                            autoFocus
                                            placeholder="••••••••••"
                                            passwordrules={passwordRules}
                                        />

                                        <InputError
                                            message={errors.password}
                                        />

                                        {passwordRules && (
                                            <p className="font-mono text-[10px] leading-relaxed text-muted-foreground">
                                                A senha deve atender aos
                                                requisitos de segurança
                                                definidos pelo sistema.
                                            </p>
                                        )}
                                    </div>

                                    {/* Confirmar senha */}
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password_confirmation"
                                            className={labelClassName}
                                        >
                                            Confirmar nova senha
                                        </Label>

                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                            placeholder="••••••••••"
                                            passwordrules={passwordRules}
                                        />

                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>

                                    {/* Submit */}
                                    <Button
                                        type="submit"
                                        className="mt-1 w-full"
                                        disabled={processing}
                                        data-test="reset-password-button"
                                    >
                                        {processing && <Spinner />}
                                        {processing
                                            ? 'Redefinindo senha...'
                                            : 'Redefinir senha →'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>

                <p className="text-center font-mono text-xs text-muted-foreground">
                    Após redefinir sua senha, você poderá acessar sua conta
                    normalmente.
                </p>
            </div>
        </>
    );
}

ResetPassword.layout = {
    title: 'Redefinir senha',
    description: 'Defina uma nova senha para sua conta',
};