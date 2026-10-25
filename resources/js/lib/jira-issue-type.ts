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

export function issueTypeHint(name: string): string | undefined {
    return ISSUE_TYPE_HINTS[name.trim().toLowerCase()];
}

/** Only Story is a requirement by nature; every other type is an explicit choice. */
export function isStory(name: string): boolean {
    return name.trim().toLowerCase() === 'story';
}
