export type JiraIntegration = {
    site_url: string;
    connected_by: string | null;
    connected_at: string | null;
    can_disconnect: boolean;
};

export type JiraProject = {
    key: string;
    name: string;
};

export type JiraIssueType = {
    name: string;
    selected_by_default: boolean;
};