import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Briefcase } from 'lucide-react';
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
}: {
    myProjects: ProjectSummary[];
}) {
    const { currentProject } = usePage().props;

    return (
        <>
            <Head title="Projects" />

            <div className="mx-auto w-full max-w-2xl space-y-8">
                <Heading
                    title="Projects"
                    description="Pick a project to work on, or create a new one."
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
                                    Ask a project owner to send you an
                                    invitation link, or create your own
                                    project.
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
                                    After creating, you can generate a
                                    revocable invitation link for each person
                                    on the project page.
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
