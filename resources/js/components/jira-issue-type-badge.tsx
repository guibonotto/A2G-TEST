import { Badge } from '@/components/ui/badge';
import { isStory, issueTypeHint } from '@/lib/jira-issue-type';

/**
 * The Jira issue type next to a requirement. Imports that took in Tasks left
 * team chores looking exactly like requirements; the type tells them apart,
 * with stories highlighted and the rest kept muted.
 */
export function JiraIssueTypeBadge({ type }: { type: string | null }) {
    if (type === null) {
        return null;
    }

    return (
        <Badge
            variant={isStory(type) ? 'info' : 'outline'}
            className={isStory(type) ? undefined : 'text-muted-foreground'}
            title={issueTypeHint(type) ?? `Jira issue type: ${type}`}
        >
            {type}
        </Badge>
    );
}
