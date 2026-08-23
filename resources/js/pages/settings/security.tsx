import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import type { Props as ManagePasskeysProps } from '@/components/manage-passkeys';
import ManagePasskeys from '@/components/manage-passkeys';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps;

const labelClassName =
    'font-mono text-xs tracking-wide text-muted-foreground uppercase';

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Configurações de segurança" />

            <h1 className="sr-only">Configurações de segurança</h1>

            <div className="space-y-8">
                {/* Alteração de senha */}
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Alterar senha"
                        description="Mantenha sua conta protegida utilizando uma senha longa e exclusiva."
                    />

                    <Form
                        {...SecurityController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="max-w-xl space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                {/* Senha atual */}
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="current_password"
                                        className={labelClassName}
                                    >
                                        Senha atual
                                    </Label>

                                    <PasswordInput
                                        id="current_password"
                                        ref={currentPasswordInput}
                                        name="current_password"
                                        className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                        autoComplete="current-password"
                                        placeholder="••••••••••"
                                    />

                                    <InputError
                                        message={errors.current_password}
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
                                        ref={passwordInput}
                                        name="password"
                                        className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                        autoComplete="new-password"
                                        placeholder="••••••••••"
                                        passwordrules={props.passwordRules}
                                    />

                                    <InputError
                                        message={errors.password}
                                    />

                                    <p className="font-mono text-[10px] leading-relaxed text-muted-foreground">
                                        Use uma senha longa e exclusiva para
                                        aumentar a segurança da sua conta.
                                    </p>
                                </div>

                                {/* Confirmar nova senha */}
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
                                        className="h-10 rounded-lg bg-secondary font-mono text-sm focus-visible:ring-primary/15"
                                        autoComplete="new-password"
                                        placeholder="••••••••••"
                                        passwordrules={props.passwordRules}
                                    />

                                    <InputError
                                        message={
                                            errors.password_confirmation
                                        }
                                    />
                                </div>

                                {/* Salvar */}
                                <div className="flex items-center gap-4">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        {processing
                                            ? 'Atualizando...'
                                            : 'Atualizar senha'}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                {/* Passkeys */}
                <div className="border-t border-border pt-8">
                    <ManagePasskeys
                        canManagePasskeys={props.canManagePasskeys}
                        passkeys={props.passkeys}
                    />
                </div>
            </div>
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Configurações de segurança',
            href: edit(),
        },
    ],
};