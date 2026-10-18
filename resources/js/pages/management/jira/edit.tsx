import { Form, Head, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Plug } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import JiraIntegrationController from '@/actions/App/Http/Controllers/JiraIntegrationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    edit as jiraEdit,
    issueTypes as jiraIssueTypes,
    redirect as jiraOauthRedirect,
} from '@/routes/jira';
import type { JiraIntegration, JiraIssueType, JiraProject } from '@/types';

type Props = {
    integration: JiraIntegration | null;
    projects: JiraProject[];
    error: string | null;
};

type ImportFormData = {
    project_key: string;
    issue_types: string[];
};

/**
 * What each Jira issue type means for a requirement, so the user is not
 * guessing which ones to bring over. Only Story is a requirement by nature;
 * importing Task and Epic by default is what filled the requirements list
 * with team chores like "create the Git repository".
 */
const ISSUE_TYPE_HINTS: Record<string, string> = {
    story: 'A user requirement. This is what usually becomes a requirement.',
    epic: 'A grouping of stories, not a requirement on its own.',
    task: 'Team work, normally not a requirement.',
    'sub-task': 'A slice of another issue, normally not a requirement.',
    subtask: 'A slice of another issue, normally not a requirement.',
    bug: 'A defect report, not a requirement.',
};

function issueTypeHint(name: string): string | undefined {
    return ISSUE_TYPE_HINTS[name.trim().toLowerCase()];
}

/** Only Story is pre-ticked; everything else is an explicit choice. */
function isStory(name: string): boolean {
    return name.trim().toLowerCase() === 'story';
}

export default function JiraIntegrationEdit({
    integration,
    projects,
    error,
}: Props) {
    const { currentProject } = usePage().props;
    const [issueTypeList, setIssueTypeList] = useState<JiraIssueType[]>([]);
    const [loadingIssueTypes, setLoadingIssueTypes] = useState(false);
    const [issueTypesError, setIssueTypesError] = useState<string | null>(null);

    const importForm = useForm<ImportFormData>({
        project_key: '',
        issue_types: [],
    });

    const selectedProject = projects.find(
        (project) => project.key === importForm.data.project_key,
    );

    async function handleProjectChange(projectKey: string) {
        setIssueTypesError(null);
        setIssueTypeList([]);
        importForm.setData({ project_key: projectKey, issue_types: [] });

        setLoadingIssueTypes(true);

        try {
            const response = await fetch(jiraIssueTypes.url({ projectKey }));

            if (!response.ok) {
                throw new Error('request failed');
            }

            const data: { issue_types: JiraIssueType[] } =
                await response.json();
            setIssueTypeList(data.issue_types);
            importForm.setData(
                'issue_types',
                data.issue_types
                    .filter((type) => isStory(type.name))
                    .map((type) => type.name),
            );
        } catch {
            setIssueTypesError(
                'Could not load the issue types for this project.',
            );
        } finally {
            setLoadingIssueTypes(false);
        }
    }

    function toggleIssueType(name: string, checked: boolean) {
        importForm.setData(
            'issue_types',
            checked
                ? [...importForm.data.issue_types, name]
                : importForm.data.issue_types.filter((type) => type !== name),
        );
    }

    function submitImport(e: FormEvent) {
        e.preventDefault();
        importForm.post(JiraIntegrationController.import.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Jira integration" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Jira integration"
                    description="Import stories from Jira as requirements."
                />

                {error && (
                    <Alert variant="destructive">
                        <AlertTitle>Failed to load Jira data</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {integration === null ? (
                    <Card className="flex max-w-2xl flex-col items-start gap-5 p-6">
                        <div>
                            <p className="text-sm font-medium">
                                No Jira site connected
                            </p>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Connecting lets you pull Jira issues into this
                                system as requirements, so test cases can be
                                linked to them.
                            </p>
                        </div>

                        <ol className="flex flex-col gap-2 text-sm">
                            <li className="flex gap-2">
                                <span className="font-medium">1.</span>
                                <span>
                                    Authorize your Atlassian account. You need
                                    access to the Jira site you want to read.
                                </span>
                            </li>
                            <li className="flex gap-2">
                                <span className="font-medium">2.</span>
                                <span>Choose the Jira project.</span>
                            </li>
                            <li className="flex gap-2">
                                <span className="font-medium">3.</span>
                                <span>
                                    Select which issue types become
                                    requirements.
                                </span>
                            </li>
                        </ol>

                        <div className="rounded-lg bg-muted px-4 py-3 text-sm text-muted-foreground">
                            <p className="font-medium text-foreground">
                                Permissions requested
                            </p>
                            <p className="mt-1">
                                Read-only access to your Jira issues and user
                                list. Nothing is written back to Jira.
                            </p>
                        </div>

                        <Button asChild>
                            <a href={jiraOauthRedirect.url()}>
                                <Plug /> Connect to Jira
                            </a>
                        </Button>
                    </Card>
                ) : (
                    <>
                        <Card className="flex max-w-2xl flex-col gap-2 p-6">
                            <p className="text-sm">
                                Connected to{' '}
                                <span className="font-medium">
                                    {integration.site_url}
                                </span>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {integration.connected_by &&
                                    `Connected by ${integration.connected_by}`}
                                {integration.connected_at &&
                                    ` on ${new Date(integration.connected_at).toLocaleDateString('en-US')}`}
                            </p>

                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="mt-2 w-fit"
                                    >
                                        Disconnect
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>Disconnect Jira?</DialogTitle>
                                    <DialogDescription>
                                        This disconnects {integration.site_url}{' '}
                                        for the whole team, not just for you.
                                        Requirements already imported are kept,
                                        but they stop being updated from Jira
                                        until someone connects again.
                                    </DialogDescription>

                                    <Form
                                        {...JiraIntegrationController.destroy.form()}
                                    >
                                        {({ processing }) => (
                                            <DialogFooter className="gap-2">
                                                <DialogClose asChild>
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                    >
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    type="submit"
                                                    variant="destructive"
                                                    disabled={processing}
                                                >
                                                    Disconnect
                                                </Button>
                                            </DialogFooter>
                                        )}
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        </Card>

                        <Card className="flex max-w-2xl flex-col gap-4 p-6">
                            <Heading
                                title="Import requirements"
                                description="Choose a Jira project and which issue types should become requirements."
                            />

                            <form
                                onSubmit={submitImport}
                                className="flex flex-col gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="jira-project">
                                        Jira project
                                    </Label>

                                    <Select
                                        value={importForm.data.project_key}
                                        onValueChange={handleProjectChange}
                                    >
                                        <SelectTrigger
                                            id="jira-project"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select a project" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {projects.map((project) => (
                                                <SelectItem
                                                    key={project.key}
                                                    value={project.key}
                                                >
                                                    {project.name} (
                                                    {project.key})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    {selectedProject && currentProject && (
                                        <p className="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
                                            <span>
                                                Jira:{' '}
                                                <span className="font-medium text-foreground">
                                                    {selectedProject.name}
                                                </span>
                                            </span>
                                            <ArrowRight
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            <span>
                                                A2G:{' '}
                                                <span className="font-medium text-foreground">
                                                    {currentProject.name}
                                                </span>
                                            </span>
                                        </p>
                                    )}

                                    <InputError
                                        message={importForm.errors.project_key}
                                    />
                                </div>

                                {loadingIssueTypes && (
                                    <p className="text-sm text-muted-foreground">
                                        Loading issue types...
                                    </p>
                                )}

                                {issueTypesError && (
                                    <Alert variant="destructive">
                                        <AlertDescription>
                                            {issueTypesError}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                {issueTypeList.length > 0 && (
                                    <div className="flex flex-col gap-3 rounded-lg border p-4">
                                        <p className="text-sm text-muted-foreground">
                                            Select which Jira issue types should
                                            be imported as requirements.
                                        </p>

                                        <div className="flex flex-col gap-1">
                                            {issueTypeList.map((issueType) => {
                                                const hint = issueTypeHint(
                                                    issueType.name,
                                                );

                                                return (
                                                    <div
                                                        key={issueType.name}
                                                        className="flex items-start gap-3 rounded-md px-2 py-2 hover:bg-muted/50"
                                                    >
                                                        <Checkbox
                                                            id={`issue-type-${issueType.name}`}
                                                            className="mt-0.5"
                                                            checked={importForm.data.issue_types.includes(
                                                                issueType.name,
                                                            )}
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                toggleIssueType(
                                                                    issueType.name,
                                                                    checked ===
                                                                        true,
                                                                )
                                                            }
                                                        />

                                                        <div className="flex flex-col gap-0.5">
                                                            <Label
                                                                htmlFor={`issue-type-${issueType.name}`}
                                                            >
                                                                {issueType.name}
                                                            </Label>

                                                            {hint && (
                                                                <span className="text-sm text-muted-foreground">
                                                                    {hint}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        <InputError
                                            message={
                                                importForm.errors.issue_types
                                            }
                                        />
                                    </div>
                                )}

                                <Button
                                    type="submit"
                                    className="w-fit"
                                    disabled={
                                        importForm.processing ||
                                        importForm.data.issue_types.length === 0
                                    }
                                >
                                    {importForm.data.issue_types.length > 0
                                        ? `Import ${importForm.data.issue_types.length} issue type(s)`
                                        : 'Import requirements'}
                                </Button>
                            </form>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

JiraIntegrationEdit.layout = {
    breadcrumbs: [{ title: 'Jira integration', href: jiraEdit() }],
};
