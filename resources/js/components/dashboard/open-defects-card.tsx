import { Link } from '@inertiajs/react';
import { Bug, CheckCircle2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { show as showTestCase } from '@/routes/test-cases';
import type { OpenDefect, OpenDefects } from '@/types/dashboard';

/**
 * Requirements whose test cases have Bugs still open in Jira. It closes the
 * traceability chain requirement → test case → execution → defect, so the
 * team sees which parts of the scope are currently broken.
 */
export function OpenDefectsCard({ data }: { data: OpenDefects }) {
    const affectedRequirements = data.groups.filter(
        (group) => group.requirement !== null,
    ).length;

    return (
        <Card className="lg:col-span-2">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Bug
                        className="size-4 text-destructive"
                        aria-hidden="true"
                    />
                    Requirements with open defects
                </CardTitle>
                <CardDescription>
                    {data.total === 0
                        ? 'Bugs reported to Jira from failed executions show up here until they are resolved.'
                        : `${data.total} open defect(s) in Jira affecting ${affectedRequirements} requirement(s).`}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {data.total === 0 ? (
                    <div className="flex flex-col items-center gap-2 py-8 text-center">
                        <CheckCircle2
                            className="size-6 text-success"
                            aria-hidden="true"
                        />
                        <p className="text-sm text-muted-foreground">
                            No open defects
                        </p>
                    </div>
                ) : (
                    <ul className="flex flex-col divide-y">
                        {data.groups.map((group) => (
                            <li
                                key={group.requirement?.id ?? 'none'}
                                className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    {group.requirement ? (
                                        <>
                                            {group.requirement.url ? (
                                                <a
                                                    href={group.requirement.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-sm font-medium text-primary underline-offset-4 hover:underline"
                                                >
                                                    {group.requirement.code}
                                                </a>
                                            ) : (
                                                <span className="text-sm font-medium">
                                                    {group.requirement.code}
                                                </span>
                                            )}
                                            <span className="min-w-0 flex-1 truncate text-sm">
                                                {group.requirement.title}
                                            </span>
                                        </>
                                    ) : (
                                        <span className="flex-1 text-sm text-muted-foreground">
                                            Test cases without a linked
                                            requirement
                                        </span>
                                    )}
                                    <Badge variant="destructive">
                                        {group.defects.length} open
                                    </Badge>
                                </div>

                                <ul className="flex flex-col gap-1.5 pl-3">
                                    {group.defects.map((defect) => (
                                        <DefectRow
                                            key={defect.key}
                                            defect={defect}
                                        />
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}

function DefectRow({ defect }: { defect: OpenDefect }) {
    const label = `${defect.key}${defect.status ? ` · ${defect.status}` : ''}`;

    return (
        <li className="flex flex-wrap items-center gap-2 text-sm">
            {defect.url ? (
                <Badge asChild variant="outline">
                    <a href={defect.url} target="_blank" rel="noreferrer">
                        {label}
                    </a>
                </Badge>
            ) : (
                <Badge variant="outline">{label}</Badge>
            )}
            <span className="text-muted-foreground">from</span>
            <Link
                href={showTestCase(defect.testCase.id)}
                className="min-w-0 truncate text-primary underline-offset-4 hover:underline"
            >
                {defect.testCase.title}
            </Link>
        </li>
    );
}

/** Shown while the deferred prop loads, shaped like the card it replaces. */
export function OpenDefectsCardSkeleton() {
    return (
        <Card className="lg:col-span-2">
            <CardHeader>
                <Skeleton className="h-5 w-64" />
                <Skeleton className="h-4 w-80" />
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-4 w-1/2" />
            </CardContent>
        </Card>
    );
}
