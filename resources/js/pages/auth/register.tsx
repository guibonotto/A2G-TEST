import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import AppBrand from '@/components/app-brand';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasswordRequirements from '@/components/password-requirements';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { home, login } from '@/routes';
import { store } from '@/routes/register';

/**
 * Administrator is deliberately absent: a new account cannot grant itself
 * system-wide access.
 */
const roles = [
    {
        id: 'role-qa',
        value: 'qa',
        name: 'QA / Tester',
        desc: 'Create and edit test cases',
    },
    {
        id: 'role-dev',
        value: 'developer',
        name: 'Developer',
        desc: 'View and execute test cases',
    },
    {
        id: 'role-viewer',
        value: 'viewer',
        name: 'Viewer',
        desc: 'Read only',
    },
];

export default function Register() {
    const [selectedRole, setSelectedRole] = useState('');
    const [password, setPassword] = useState('');

    return (
        <>
            <Head title="Create account" />

            <div className="bg-grid" />

            <div className="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center gap-6 px-6 py-12 fade-in">
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
                        <div>
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                Create account
                            </h1>

                            <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                                All fields are required.
                            </p>
                        </div>

                        <Form
                            {...store.form()}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            className="flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="first_name">
                                                First name
                                            </Label>

                                            <Input
                                                id="first_name"
                                                className="h-10 rounded-lg bg-secondary text-sm"
                                                type="text"
                                                name="first_name"
                                                placeholder="John"
                                                autoFocus
                                                autoComplete="given-name"
                                                aria-invalid={
                                                    errors.first_name
                                                        ? 'true'
                                                        : 'false'
                                                }
                                            />

                                            <InputError
                                                message={errors.first_name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="last_name">
                                                Last name
                                            </Label>

                                            <Input
                                                id="last_name"
                                                className="h-10 rounded-lg bg-secondary text-sm"
                                                type="text"
                                                name="last_name"
                                                placeholder="Smith"
                                                autoComplete="family-name"
                                                aria-invalid={
                                                    errors.last_name
                                                        ? 'true'
                                                        : 'false'
                                                }
                                            />

                                            <InputError
                                                message={errors.last_name}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>

                                        <Input
                                            id="email"
                                            className="h-10 rounded-lg bg-secondary text-sm"
                                            type="email"
                                            name="email"
                                            placeholder="john@company.com"
                                            autoComplete="email"
                                            aria-invalid={
                                                errors.email ? 'true' : 'false'
                                            }
                                        />

                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Password
                                        </Label>

                                        <PasswordInput
                                            id="password"
                                            className="h-10 rounded-lg bg-secondary text-sm"
                                            name="password"
                                            value={password}
                                            onChange={(event) =>
                                                setPassword(event.target.value)
                                            }
                                            autoComplete="new-password"
                                            aria-invalid={
                                                errors.password
                                                    ? 'true'
                                                    : 'false'
                                            }
                                            aria-describedby="password-rules"
                                        />

                                        <PasswordRequirements
                                            id="password-rules"
                                            value={password}
                                            className="mt-1"
                                        />

                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Confirm password
                                        </Label>

                                        <PasswordInput
                                            id="password_confirmation"
                                            className="h-10 rounded-lg bg-secondary text-sm"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            aria-invalid={
                                                errors.password_confirmation
                                                    ? 'true'
                                                    : 'false'
                                            }
                                        />

                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>

                                    <fieldset className="grid gap-2">
                                        <legend className="mb-2 text-sm leading-none font-medium">
                                            Access role
                                        </legend>

                                        {/* One per line: three options in a
                                            two-column grid left a hole that
                                            read as a missing fourth choice. */}
                                        <div className="flex flex-col gap-2">
                                            {roles.map((role) => {
                                                const isSelected =
                                                    selectedRole === role.value;

                                                return (
                                                    <div key={role.id}>
                                                        <input
                                                            type="radio"
                                                            name="role"
                                                            id={role.id}
                                                            value={role.value}
                                                            checked={isSelected}
                                                            onChange={() =>
                                                                setSelectedRole(
                                                                    role.value,
                                                                )
                                                            }
                                                            className="peer sr-only"
                                                        />

                                                        <label
                                                            htmlFor={role.id}
                                                            className={cn(
                                                                'flex w-full cursor-pointer items-start gap-3 rounded-lg border bg-background px-4 py-3 transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-ring',
                                                                isSelected
                                                                    ? 'border-primary bg-primary/8'
                                                                    : 'border-input hover:border-primary/40',
                                                            )}
                                                        >
                                                            <span
                                                                className={cn(
                                                                    'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border transition-colors',
                                                                    isSelected
                                                                        ? 'border-primary'
                                                                        : 'border-input',
                                                                )}
                                                                aria-hidden="true"
                                                            >
                                                                {isSelected && (
                                                                    <span className="size-2 rounded-full bg-primary" />
                                                                )}
                                                            </span>

                                                            <span className="flex flex-col gap-0.5">
                                                                <span
                                                                    className={cn(
                                                                        'text-sm font-semibold',
                                                                        isSelected &&
                                                                            'text-primary',
                                                                    )}
                                                                >
                                                                    {role.name}
                                                                </span>

                                                                <span className="text-sm leading-relaxed text-muted-foreground">
                                                                    {role.desc}
                                                                </span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        <InputError message={errors.role} />
                                    </fieldset>

                                    <Button
                                        type="submit"
                                        className="mt-1 w-full"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        {processing
                                            ? 'Creating account...'
                                            : 'Create account'}
                                    </Button>
                                </>
                            )}
                        </Form>

                        <p className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <Link
                                href={login()}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Log in
                            </Link>
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
