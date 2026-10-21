export type Classification = {
    id: number;
    name: string;
};

export type TestTemplate = {
    id: number;
    title: string;
    tested_method: string | null;
};

export type TestTemplateStep = {
    description: string;
    expected_result: string | null;
};

/** Payload returned by `test-templates.show`, used to pre-fill the test case form. */
export type TestTemplateData = {
    description: string | null;
    classification_id: number | null;
    steps: TestTemplateStep[];
};

export type TestCaseStatusColor =
    | 'success'
    | 'destructive'
    | 'warning'
    | 'secondary'
    | 'info'
    | 'blue'
    | 'purple'
    | 'orange'
    | 'pink';

export type TestCaseStatus = {
    id: number;
    name: string;
    description: string | null;
    color: TestCaseStatusColor;
    category: TestCaseStatusCategory;
};

export type TestCaseStatusCategory =
    | 'in_progress'
    | 'approved'
    | 'failed'
    | 'cancelled';

export type AssignableUser = {
    id: number;
    name: string;
};

export type TestCaseListItem = {
    id: number;
    title: string;
    tested_method: string | null;
    classification: Classification | null;
    status: TestCaseStatus | null;
    latest_execution: Execution | null;
    creator: { id: number; name: string } | null;
    assignee: AssignableUser | null;
    requirements: LinkedRequirement[];
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
    tested_method: string | null;
    description: string | null;
    classification: Classification | null;
    template: TestTemplate | null;
    status: TestCaseStatus | null;
    latest_execution: Execution | null;
    creator: { id: number; name: string } | null;
    assignee: AssignableUser | null;
    steps: TestStep[];
    executions: Execution[];
    requirements: LinkedRequirement[];
    created_at: string;
};

export type Execution = {
    id: number;
    status: string;
    comment: string | null;
    obtained_output: string | null;
    execution_date: string;
    executor: { id: number; name: string } | null;
    evidences: Evidence[];
};

export type LinkedRequirement = {
    id: number;
    code: string;
    title: string;
};

export type TestCaseFilters = {
    search: string;
    classification_id: number | null;
    situation_id: number | null;
    execution_result: string | null;
    assigned_to_me: boolean;
};

export type Evidence = {
    id: number;
    file_name: string;
    mime_type: string;
    size: number;
    uploaded_at: string;
};
