export type JiraIntegration = {
    site_url: string;
    connected_by: string | null;
    connected_at: string | null;
    can_disconnect: boolean;
    /** False for connections made before defect reporting, which only have read access. */
    can_report_defects: boolean;
};

export type JiraProject = {
    key: string;
    name: string;
};

export type JiraIssueType = {
    name: string;
    selected_by_default: boolean;
};

export type JiraImportPreviewIssue = {
    key: string;
    title: string;
    issue_type: string | null;
    state: 'new' | 'existing' | 'deleted';
};

export type JiraImportPreview = {
    issues: JiraImportPreviewIssue[];
    truncated: boolean;
};
