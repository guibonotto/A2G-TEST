export type CurrentProject = {
    id: number;
    uuid: string;
    name: string;
    owner_id: number;
};

export type ProjectSummary = {
    id: number;
    uuid: string;
    name: string;
    isOwner: boolean;
    role: {
        name: string;
        slug: string;
    } | null;
    /** Live test cases in the project, so two projects are told apart. */
    test_cases_count: number;
    /** When a test case last changed; null while the project is empty. */
    last_activity_at: string | null;
};
