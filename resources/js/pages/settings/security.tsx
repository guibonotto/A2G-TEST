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
            <Head title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <div className="space-y-8">
                {/* Password change */}
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Change password"
                        description="Keep your account secure by using a long, unique password."
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
                                {/* Current password */}
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="current_password"
                                        className={labelClassName}
                                    >
                                        Current password
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

                                {/* New password */}
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="password"
                                        className={labelClassName}
                                    >
                                        New password
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
                                        Use a long, unique password to keep
                                        your account more secure.
                                    </p>
                                </div>

                                {/* Confirm new password */}
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="password_confirmation"
                                        className={labelClassName}
                                    >
                                        Confirm new password
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

                                {/* Save */}
                                <div className="flex items-center gap-4">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        {processing
                                            ? 'Updating...'
                                            : 'Update password'}
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
            title: 'Security settings',
            href: edit(),
        },
    ],
};