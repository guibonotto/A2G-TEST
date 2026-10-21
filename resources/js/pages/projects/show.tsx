import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, UserRound } from 'lucide-react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import CopyButton from '@/components/copy-button';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index as projectsIndex, show as projectsShow } from '@/routes/projects';
import { index as testCasesIndex } from '@/routes/test-cases';

type Member = { id: number; name: string; email: string };
type PendingInvite = { id: number; created_at: string };
type Project = {
    uuid: string;
    name: string;
    ownerId: number;
    isOwner: boolean;
    members: Member[];
    pendingInvites: PendingInvite[];
};

export default function ProjectsShow({
    project,
    inviteLink,
}: {
    project: Project;
    inviteLink?: string;
}) {
    return (
        <>
            <Head title={project.name} />
            <div className="mx-auto w-full max-w-2xl space-y-6">
                <div className="flex items-start gap-3">
                    <CheckCircle2 className="mt-1 size-6 shrink-0 text-success" aria-hidden="true" />
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Project &ldquo;{project.name}&rdquo; is ready
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {project.isOwner
                                ? 'Manage invitations and project access below.'
                                : 'You are a member of this project.'}
                        </p>
                    </div>
                </div>

                {project.isOwner && (
                    <Card>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-semibold">
                                    Invite your team
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Generate a one-time link for each person.
                                    Links can be revoked before they are used.
                                </p>
                            </div>
                            {inviteLink && (
                                <div className="flex flex-wrap items-center gap-3 rounded-lg bg-muted px-4 py-3">
                                    <code className="min-w-0 flex-1 break-all text-sm">
                                        {inviteLink}
                                    </code>
                                    <CopyButton
                                        value={inviteLink}
                                        label="Copy invitation link"
                                        copiedLabel="Link copied"
                                        size="sm"
                                    />
                                </div>
                            )}
                            <Form
                                {...ProjectController.createInvite.form(
                                    project.uuid,
                                )}
                            >
                                {({ processing }) => (
                                    <Button type="submit" disabled={processing}>
                                        {processing && <span className="animate-spin">...</span>}
                                        Generate invitation link
                                    </Button>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {project.isOwner && (
                    <Card>
                        <CardContent className="space-y-4">
                            <p className="text-sm font-semibold">Project access</p>
                            <ul className="space-y-2">
                                {project.members.map((member) => (
                                    <li key={member.id} className="flex items-center gap-3 rounded-lg border px-3 py-2">
                                        <UserRound className="size-4 text-muted-foreground" aria-hidden="true" />
                                        <span className="min-w-0 flex-1 truncate text-sm">
                                            {member.name} ({member.email})
                                        </span>
                                        {member.id !== project.ownerId && (
                                            <Form {...ProjectController.removeMember.form([project.uuid, member.id])}>
                                                <Button type="submit" variant="destructive" size="sm">
                                                    Revoke access
                                                </Button>
                                            </Form>
                                        )}
                                    </li>
                                ))}
                            </ul>
                            {project.pendingInvites.length > 0 && (
                                <div className="space-y-2 border-t pt-4">
                                    <p className="text-sm font-medium">Pending invitations</p>
                                    {project.pendingInvites.map((invite) => (
                                        <Form key={invite.id} {...ProjectController.revokeInvite.form([project.uuid, invite.id])}>
                                            <div className="flex items-center gap-3 text-sm">
                                                <span className="flex-1 text-muted-foreground">Invitation link not used</span>
                                                <Button type="submit" variant="outline" size="sm">Revoke link</Button>
                                            </div>
                                        </Form>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="space-y-4">
                        <p className="text-sm font-semibold">Start working</p>
                        <p className="text-sm text-muted-foreground">
                            Your project is empty. Create the first test case to get going.
                        </p>
                        <Button asChild><Link href={testCasesIndex()}>Go to test cases</Link></Button>
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
