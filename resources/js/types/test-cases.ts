export type Classification = {
    id: number;
    name: string;
};

export type TestTemplate = {
    id: number;
    title: string;
};

export type TestCaseStatusColor = 'success' | 'destructive' | 'warning' | 'secondary' | 'info';

export type TestCaseStatus = {
    id: number;
    name: string;
    color: TestCaseStatusColor;
};

export type AssignableUser = {
    id: number;
    name: string;
};

export type TestCaseListItem = {
    id: number;
    title: string;
    classification: Classification | null;
    status: TestCaseStatus | null;
    creator: { id: number; name: string } | null;
    assignee: AssignableUser | null;
    steps_count: number;
    created_at: string;
};

export type TestStep = {
    id: number;
    order: number;
    description: string;
    expected_result: string | null;
};

export type TestCaseDetail = {
    id: number;
    title: string;
    description: string | null;
    classification: Classification | null;
    template: TestTemplate | null;
    status: TestCaseStatus | null;
    creator: { id: number; name: string } | null;
    assignee: AssignableUser | null;
    steps: TestStep[];
    executions: Execution[];
    created_at: string;
};

export type Execution = {
    id: number;
    status: string;
    comment: string | null;
    execution_date: string;
    executor: { id: number; name: string } | null;
};

export type TestCaseFilters = {
    search: string;
    classification_id: number | null;
    status_id: number | null;
    assigned_to_me: boolean;
};
