import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

const labelClassName =
    'font-mono text-xs tracking-wide text-muted-foreground uppercase';

export default function Profile() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address."
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {/* Name */}
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="name"
                                    className={labelClassName}
                                >
                                    Full name
                                </Label>

                                <Input
                                    id="name"
                                    className="h-10 bg-secondary font-mono text-sm"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Your full name"
                                />

                                <InputError
                                    className="mt-1"
                                    message={errors.name}
                                />
                            </div>

                            {/* Email */}
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="email"
                                    className={labelClassName}
                                >
                                    Email
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="h-10 bg-secondary font-mono text-sm"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="you@example.com"
                                />

                                <InputError
                                    className="mt-1"
                                    message={errors.email}
                                />
                            </div>

                            {/* Action */}
                            <div className="flex items-center gap-4">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    {processing
                                        ? 'Saving...'
                                        : 'Save changes'}
                                </Button>

                                {!processing && (
                                    <span className="font-mono text-[10px] text-muted-foreground">
                                        Your information will be updated
                                        immediately.
                                    </span>
                                )}
                            </div>
                        </>
                    )}
                </Form>

                <div className="border-t border-border pt-8">
                    <DeleteUser />
                </div>
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};