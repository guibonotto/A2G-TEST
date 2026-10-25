import { router, useForm } from '@inertiajs/react';
import { Bug } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import ExecutionDefectController from '@/actions/App/Http/Controllers/ExecutionDefectController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { Execution, JiraProject } from '@/types';

type Props = {
    execution: Execution;
    testCaseTitle: string;
    defaultProjectKey: string | null;
    /** Loaded on demand the first time the dialog opens; undefined until then. */
    jiraProjects: JiraProject[] | undefined;
};

/**
 * Turns a failed execution into a Bug in Jira. The issue is filled in on the
 * server with the specification, the obtained output and the evidences, so
 * the tester only picks the Jira project and confirms the summary.
 */
export function ReportDefectDialog({
    execution,
    testCaseTitle,
    defaultProjectKey,
    jiraProjects,
}: Props) {
    const [open, setOpen] = useState(false);
    const [loadingProjects, setLoadingProjects] = useState(false);

    const form = useForm({
        jira_project_key: defaultProjectKey ?? '',
        summary: `Test failed: ${testCaseTitle}`,
        attach_evidences: execution.evidences.length > 0,
    });

    function handleOpenChange(nextOpen: boolean) {
        setOpen(nextOpen);

        if (nextOpen && jiraProjects === undefined) {
            setLoadingProjects(true);
            router.reload({
                only: ['jiraProjects'],
                onFinish: () => setLoadingProjects(false),
            });
        }
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        form.post(ExecutionDefectController.store.url(execution.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    const errors = form.errors as Record<string, string | undefined>;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button type="button" size="sm" className="w-fit">
                    <Bug /> Report defect in Jira
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle>Report defect in Jira</DialogTitle>
                <DialogDescription>
                    A Bug is created with the method, input, expected and
                    obtained output, the execution details and links to the
                    covered requirements.
                </DialogDescription>

                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor={`defect-project-${execution.id}`}>
                            Jira project
                        </Label>
                        {loadingProjects ? (
                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Spinner /> Loading Jira projects...
                            </p>
                        ) : jiraProjects && jiraProjects.length > 0 ? (
                            <Select
                                value={form.data.jira_project_key}
                                onValueChange={(value) =>
                                    form.setData('jira_project_key', value)
                                }
                            >
                                <SelectTrigger
                                    id={`defect-project-${execution.id}`}
                                >
                                    <SelectValue placeholder="Select a Jira project" />
                                </SelectTrigger>
                                <SelectContent>
                                    {jiraProjects.map((project) => (
                                        <SelectItem
                                            key={project.key}
                                            value={project.key}
                                        >
                                            {project.name} ({project.key})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <p className="text-sm text-destructive">
                                Could not load the Jira projects. Check the
                                connection in Jira integration.
                            </p>
                        )}
                        <InputError message={errors.jira_project_key} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`defect-summary-${execution.id}`}>
                            Summary
                        </Label>
                        <Input
                            id={`defect-summary-${execution.id}`}
                            value={form.data.summary}
                            onChange={(e) =>
                                form.setData('summary', e.target.value)
                            }
                            maxLength={255}
                        />
                        <InputError message={errors.summary} />
                    </div>

                    {execution.evidences.length > 0 && (
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id={`defect-evidences-${execution.id}`}
                                checked={form.data.attach_evidences}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'attach_evidences',
                                        checked === true,
                                    )
                                }
                            />
                            <Label
                                htmlFor={`defect-evidences-${execution.id}`}
                                className="font-normal"
                            >
                                Attach the {execution.evidences.length} evidence
                                file(s)
                            </Label>
                        </div>
                    )}

                    <InputError message={errors.execution} />

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.jira_project_key === ''
                            }
                        >
                            {form.processing && <Spinner />}
                            Create Bug
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
