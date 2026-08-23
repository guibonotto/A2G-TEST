export type Classification = {
    id: number;
    name: string;
};

export type TestTemplate = {
    id: number;
    title: string;
};

export type TestCaseListItem = {
    id: number;
    title: string;
    classification: Classification | null;
    creator: { id: number; name: string } | null;
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
    creator: { id: number; name: string } | null;
    steps: TestStep[];
    created_at: string;
};

export type TestCaseFilters = {
    search: string;
    classification_id: number | null;
};
