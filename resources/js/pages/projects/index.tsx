import { Form, Head, usePage } from '@inertiajs/react';
import { Briefcase } from 'lucide-react';
import { useState } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import CopyButton from '@/components/copy-button';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasswordRequirements from '@/components/password-requirements';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { index as projectsIndex } from '@/routes/projects';
import type { ProjectSummary } from '@/types/projects';

type Mode = 'create' | 'join';

export default function ProjectsIndex({
    myProjects,
}: {
    myProjects: ProjectSummary[];
}) {
    const { currentProject } = usePage().props;
    const [mode, setMode] = useState<Mode>(
        myProjects.length > 0 ? 'join' : 'create',
    );
    const [password, setPassword] = useState('');

    return (
        <>
            <Head title="Projects" />

            <div className="mx-auto w-full max-w-2xl space-y-8">
                <Heading
                    title="Projects"
                    description="Pick a project to work on, or add a new one."
                />

                <section className="space-y-3">
                    <h2 className="text-sm font-semibold">Your projects</h2>

                    {myProjects.length > 0 ? (
                        <ul className="space-y-2">
                            {myProjects.map((project) => {
                                const isCurrent =
                                    currentProject?.id === project.id;

                                return (
                                    <li key={project.id}>
                                        <Form
                                            {...ProjectController.select.form(
                                                project.uuid,
                                            )}
                                        >
                                            {({ processing }) => (
                                                <div
                                                    className={cn(
                                                        'flex flex-wrap items-center gap-3 rounded-lg border px-4 py-3 transition-colors',
                                                        isCurrent
                                                            ? 'border-primary bg-primary/5'
                                                            : 'hover:border-primary/40',
                                                    )}
                                                >
                                                    <Briefcase
                                                        className="size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />

                                                    <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                                        {project.name}
                                                    </span>

                                                    {isCurrent && (
                                                        <Badge variant="info">
                                                            Open now
                                                        </Badge>
                                                    )}

                                                    <CopyButton
                                                        value={project.uuid}
                                                        label="Copy project code"
                                                        iconOnly
                                                        variant="ghost"
                                                        toastMessage="Project code copied to the clipboard."
                                                    />

                                                    <Button
                                                        type="submit"
                                                        variant={
                                                            isCurrent
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        {processing && (
                                                            <Spinner />
                                                        )}
                                                        {isCurrent
                                                            ? 'Reopen'
                                                            : 'Open'}
                                                    </Button>
                                                </div>
                                            )}
                                        </Form>
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
                                    Create a project to start writing test
                                    cases, or join one with the code a teammate
                                    shared with you.
                                </p>

                                <div className="flex flex-wrap gap-2 sm:justify-center">
                                    <Button
                                        type="button"
                                        onClick={() => setMode('create')}
                                    >
                                        Create project
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setMode('join')}
                                    >
                                        Join a project
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </section>

                <section className="space-y-4">
                    <h2 className="text-sm font-semibold">Add a project</h2>

                    <div
                        role="tablist"
                        aria-label="Add a project"
                        className="flex w-full border-b border-border"
                    >
                        <TabButton
                            selected={mode === 'create'}
                            onClick={() => setMode('create')}
                        >
                            Create
                        </TabButton>

                        <TabButton
                            selected={mode === 'join'}
                            onClick={() => setMode('join')}
                        >
                            Join with code
                        </TabButton>
                    </div>

                    {mode === 'create' ? (
                        <Form
                            {...ProjectController.store.form()}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <p className="text-sm text-muted-foreground">
                                        After creating, you will get a code to
                                        invite your team. You do not choose it —
                                        the system generates it.
                                    </p>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Project name
                                        </Label>
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

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Project password
                                        </Label>
                                        <PasswordInput
                                            id="password"
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
                                            aria-describedby="project-password-rules"
                                        />
                                        <PasswordRequirements
                                            id="project-password-rules"
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

                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Create project
                                    </Button>
                                </>
                            )}
                        </Form>
                    ) : (
                        <Form
                            {...ProjectController.join.form()}
                            resetOnSuccess={['password']}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="uuid">
                                            Project code
                                        </Label>
                                        <Input
                                            id="uuid"
                                            name="uuid"
                                            placeholder="Paste the code shared with you"
                                            autoFocus
                                            autoComplete="off"
                                            aria-invalid={
                                                errors.uuid ? 'true' : 'false'
                                            }
                                            aria-describedby="uuid-hint"
                                        />
                                        <p
                                            id="uuid-hint"
                                            className="text-sm text-muted-foreground"
                                        >
                                            36 characters, like 1a2b3c4d-5e6f-….
                                            The project owner sees it right
                                            after creating the project.
                                        </p>
                                        <InputError message={errors.uuid} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="join-password">
                                            Project password
                                        </Label>
                                        <PasswordInput
                                            id="join-password"
                                            name="password"
                                            autoComplete="new-password"
                                            aria-invalid={
                                                errors.password
                                                    ? 'true'
                                                    : 'false'
                                            }
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Join project
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </section>
            </div>
        </>
    );
}

function TabButton({
    selected,
    onClick,
    children,
}: {
    selected: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            role="tab"
            aria-selected={selected}
            onClick={onClick}
            className={cn(
                '-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors',
                selected
                    ? 'border-primary text-foreground'
                    : 'border-transparent text-muted-foreground hover:text-foreground',
            )}
        >
            {children}
        </button>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: projectsIndex(),
        },
    ],
};
