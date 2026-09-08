import { Form, Head, useForm } from '@inertiajs/react';
import { Plug } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import JiraIntegrationController from '@/actions/App/Http/Controllers/JiraIntegrationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit as jiraEdit, issueTypes as jiraIssueTypes, redirect as jiraOauthRedirect } from '@/routes/jira';
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

export default function JiraIntegrationEdit({ integration, projects, error }: Props) {
    const [issueTypeList, setIssueTypeList] = useState<JiraIssueType[]>([]);
    const [loadingIssueTypes, setLoadingIssueTypes] = useState(false);
    const [issueTypesError, setIssueTypesError] = useState<string | null>(null);

    const importForm = useForm<ImportFormData>({ project_key: '', issue_types: [] });

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

            const data: { issue_types: JiraIssueType[] } = await response.json();
            setIssueTypeList(data.issue_types);
            importForm.setData(
                'issue_types',
                data.issue_types.filter((type) => type.selected_by_default).map((type) => type.name),
            );
        } catch {
            setIssueTypesError('Could not load the issue types for this project.');
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
        importForm.post(JiraIntegrationController.import.url(), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Jira integration" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Jira integration"
                    description="Connect a Jira site to import cards as system requirements."
                />

                {error && (
                    <Alert variant="destructive">
                        <AlertTitle>Failed to load Jira data</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {integration === null ? (
                    <Card className="flex flex-col items-start gap-4 p-6">
                        <p className="text-sm text-muted-foreground">
                            No Jira site connected. Click the button below to authorize access with your Atlassian account.
                        </p>
                        <Button asChild>
                            <a href={jiraOauthRedirect.url()}>
                                <Plug /> Connect to Jira
                            </a>
                        </Button>
                    </Card>
                ) : (
                    <>
                        <Card className="flex flex-col gap-2 p-6">
                            <p className="text-sm">
                                Connected to <span className="font-medium">{integration.site_url}</span>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {integration.connected_by && `Connected by ${integration.connected_by}`}
                                {integration.connected_at &&
                                    ` on ${new Date(integration.connected_at).toLocaleDateString('en-US')}`}
                            </p>

                            <Form {...JiraIntegrationController.destroy.form()} className="mt-2 w-fit">
                                {({ processing }) => (
                                    <Button type="submit" variant="destructive" size="sm" disabled={processing}>
                                        Disconnect
                                    </Button>
                                )}
                            </Form>
                        </Card>

                        <Card className="flex flex-col gap-4 p-6">
                            <Heading
                                title="Import requirements"
                                description="Choose a project and which issue types should become requirements."
                            />

                            <form onSubmit={submitImport} className="flex flex-col gap-4">
                                <div className="grid max-w-sm gap-2">
                                    <Select value={importForm.data.project_key} onValueChange={handleProjectChange}>
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select a project" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {projects.map((project) => (
                                                <SelectItem key={project.key} value={project.key}>
                                                    {project.name} ({project.key})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={importForm.errors.project_key} />
                                </div>

                                {loadingIssueTypes && (
                                    <p className="text-sm text-muted-foreground">Loading issue types...</p>
                                )}

                                {issueTypesError && (
                                    <Alert variant="destructive">
                                        <AlertDescription>{issueTypesError}</AlertDescription>
                                    </Alert>
                                )}

                                {issueTypeList.length > 0 && (
                                    <div className="flex flex-col gap-3 rounded-lg border p-4">
                                        <p className="text-sm text-muted-foreground">
                                            Select which Jira issue types should be imported as requirements.
                                        </p>

                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {issueTypeList.map((issueType) => (
                                                <div key={issueType.name} className="flex items-center gap-2">
                                                    <Checkbox
                                                        id={`issue-type-${issueType.name}`}
                                                        checked={importForm.data.issue_types.includes(issueType.name)}
                                                        onCheckedChange={(checked) =>
                                                            toggleIssueType(issueType.name, checked === true)
                                                        }
                                                    />
                                                    <Label htmlFor={`issue-type-${issueType.name}`}>
                                                        {issueType.name}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>

                                        <InputError message={importForm.errors.issue_types} />
                                    </div>
                                )}

                                <Button
                                    type="submit"
                                    className="w-fit"
                                    disabled={importForm.processing || importForm.data.issue_types.length === 0}
                                >
                                    Import requirements
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
