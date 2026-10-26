import { Form, Head, Link, router } from '@inertiajs/react';
import { Briefcase } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { index as projectsIndex } from '@/routes/projects';
import { show as projectsShow } from '@/routes/projects';
import type { ProjectSummary } from '@/types/projects';

export default function ProjectsIndex({
    myProjects,
    currentProject,
}: {
    myProjects: ProjectSummary[];
    currentProject?: { id: number } | null;
}) {
    const [inviteValue, setInviteValue] = useState('');
    const [inviteError, setInviteError] = useState<string>();

    function joinProject(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const value = inviteValue.trim();

        if (!value) {
            setInviteError('Enter an invitation link or token.');

            return;
        }

        let token = value;

        try {
            const url = new URL(value, window.location.origin);
            const parts = url.pathname.split('/').filter(Boolean);

            if (parts.at(-2) === 'invite' && parts.at(-1)) {
                token = parts.at(-1) as string;
            }
        } catch {
            setInviteError('Enter a valid invitation link or token.');

            return;
        }

        setInviteError(undefined);
        router.visit(ProjectController.invite.url(token));
    }

    return (
        <>
            <Head title="Projects" />

            <div className="mx-auto w-full max-w-2xl space-y-8">
                <Heading
                    title="Projects"
                    description="Pick a project to work on, or create a new one."
                />

                <section className="space-y-4">
                    <h2 className="text-sm font-semibold">Join a project</h2>
                    <Card>
                        <CardContent>
                            <form onSubmit={joinProject} className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    Paste the invitation link or token sent by
                                    the project owner to get access.
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor="invitation">
                                        Invitation link or token
                                    </Label>
                                    <Input
                                        id="invitation"
                                        value={inviteValue}
                                        onChange={(event) =>
                                            setInviteValue(event.target.value)
                                        }
                                        placeholder="https://.../projects/invite/..."
                                        autoComplete="off"
                                        aria-invalid={
                                            inviteError ? 'true' : 'false'
                                        }
                                    />
                                    <InputError message={inviteError} />
                                </div>
                                <Button type="submit">Join project</Button>
                            </form>
                        </CardContent>
                    </Card>
                </section>

                <section className="space-y-3">
                    <h2 className="text-sm font-semibold">Your projects</h2>

                    {myProjects.length > 0 ? (
                        <ul className="space-y-2">
                            {myProjects.map((project) => {
                                const isCurrent =
                                    currentProject?.id === project.id;

                                return (
                                    <li
                                        key={project.id}
                                        className="flex items-center gap-2"
                                    >
                                        <Form
                                            {...ProjectController.select.form(
                                                project.uuid,
                                            )}
                                            className="flex min-w-0 flex-1"
                                        >
                                            {({ processing }) => (
                                                <button
                                                    type="submit"
                                                    disabled={processing}
                                                    className={cn(
                                                        'grid w-full min-w-0 gap-3 rounded-lg border px-4 py-3 text-left transition-colors sm:grid-cols-[minmax(0,1fr)_11rem] sm:items-center',
                                                        isCurrent
                                                            ? 'border-primary bg-primary/5'
                                                            : 'hover:border-primary/40',
                                                    )}
                                                >
                                                    <span className="flex min-w-0 items-center gap-3">
                                                        <Briefcase
                                                            className="size-4 shrink-0 text-muted-foreground"
                                                            aria-hidden="true"
                                                        />
                                                        <span className="min-w-0 truncate text-sm font-medium">
                                                            {project.name}
                                                        </span>
                                                        {isCurrent && (
                                                            <Badge variant="info">
                                                                Open now
                                                            </Badge>
                                                        )}
                                                    </span>
                                                    <span className="flex flex-col text-sm text-muted-foreground">
                                                        <span>
                                                            <span className="mr-2 font-medium text-foreground sm:hidden">
                                                                Role:
                                                            </span>
                                                            {project.role
                                                                ?.name ??
                                                                'No role'}
                                                        </span>

                                                        <span className="text-xs">
                                                            {
                                                                project.test_cases_count
                                                            }{' '}
                                                            case(s)
                                                            {project.last_activity_at &&
                                                                ` · updated ${new Date(
                                                                    project.last_activity_at,
                                                                ).toLocaleDateString()}`}
                                                        </span>
                                                    </span>
                                                </button>
                                            )}
                                        </Form>
                                        {project.isOwner && (
                                            <Button
                                                asChild
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Link
                                                    href={projectsShow(
                                                        project.uuid,
                                                    )}
                                                >
                                                    Manage access
                                                </Link>
                                            </Button>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    ) : (
                        <Card>
                            <CardContent className="flex flex-col items-start gap-3 py-8 text-center sm:items-center">
                                <Briefcase
                                    className="size-8 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <p className="text-sm font-medium">
                                    You are not part of any project yet
                                </p>
                                <p className="max-w-sm text-sm text-muted-foreground">
                                    Ask a project owner to send you an
                                    invitation link, or create your own project.
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </section>

                <section className="space-y-4">
                    <h2 className="text-sm font-semibold">Create a project</h2>
                    <Form
                        {...ProjectController.store.form()}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <p className="text-sm text-muted-foreground">
                                    After creating, you can generate a revocable
                                    invitation link for each person on the
                                    project page.
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Project name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="e.g. Alpha Project"
                                        autoFocus
                                        autoComplete="off"
                                        aria-invalid={
                                            errors.name ? 'true' : 'false'
                                        }
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Create project
                                </Button>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projects', href: projectsIndex() }],
};
