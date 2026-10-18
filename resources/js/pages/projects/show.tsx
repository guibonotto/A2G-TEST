import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import CopyButton from '@/components/copy-button';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    index as projectsIndex,
    show as projectsShow,
} from '@/routes/projects';
import { index as testCasesIndex } from '@/routes/test-cases';

type Project = {
    uuid: string;
    name: string;
    isOwner: boolean;
};

export default function ProjectsShow({ project }: { project: Project }) {
    const inviteText = `Join the project "${project.name}" on A2G TEST. Project ID: ${project.uuid}`;

    return (
        <>
            <Head title={project.name} />

            <div className="mx-auto w-full max-w-2xl space-y-6">
                <div className="flex items-start gap-3">
                    <CheckCircle2
                        className="mt-1 size-6 shrink-0 text-success"
                        aria-hidden="true"
                    />

                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Project &ldquo;{project.name}&rdquo; is ready
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            {project.isOwner
                                ? 'Two things left: invite your team and create the first test case.'
                                : 'You are a member of this project.'}
                        </p>
                    </div>
                </div>

                {project.isOwner && (
                    <Card>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-semibold">
                                    Step 1 — Invite your team
                                </p>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    Send this code to whoever will work with
                                    you. They also need the project password you
                                    just chose.
                                </p>
                            </div>

                            <div className="flex flex-wrap items-center gap-3 rounded-lg bg-muted px-4 py-3">
                                <code className="min-w-0 flex-1 text-sm break-all">
                                    {project.uuid}
                                </code>

                                <CopyButton
                                    value={project.uuid}
                                    label="Copy code"
                                    copiedLabel="Copied"
                                    size="sm"
                                />
                            </div>

                            <CopyButton
                                value={inviteText}
                                label="Copy invite message"
                                copiedLabel="Invite copied"
                                toastMessage="Invite message copied to the clipboard."
                                variant="secondary"
                                size="sm"
                            />

                            <p className="text-sm text-muted-foreground">
                                You can find this code again in Projects at any
                                time.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="text-sm font-semibold">
                                Step 2 — Start working
                            </p>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Your project is empty. Create the first test
                                case to get going.
                            </p>
                        </div>

                        <Button asChild>
                            <Link href={testCasesIndex()}>
                                Go to test cases
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ProjectsShow.layout = (props: { project: Project }) => ({
    breadcrumbs: [
        { title: 'Projects', href: projectsIndex() },
        { title: props.project.name, href: projectsShow(props.project.uuid) },
    ],
});
