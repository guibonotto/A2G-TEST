import type { TestCaseStatusColor } from '@/types/test-cases';

export type StatusBreakdownItem = {
    id: number | string;
    name: string;
    description: string | null;
    color: TestCaseStatusColor;
    category: string;
    total: number;
};

export type ClassificationBreakdownItem = {
    id: number;
    name: string;
    total: number;
};

export type WorkloadItem = {
    name: string;
    total: number;
};

export type CreationTrendPoint = {
    date: string;
    created: number;
    cumulative: number;
};

export type DashboardStats = {
    total: number;
    unassigned: number;
    createdLast7Days: number;
    statusesInUse: number;
};

export type OpenDefect = {
    key: string;
    /** Last status read from Jira; null until it is first read. */
    status: string | null;
    url: string | null;
    testCase: { id: number; title: string };
};

export type OpenDefectGroup = {
    /** Null groups the defects of test cases not linked to any requirement. */
    requirement: {
        id: number;
        code: string;
        title: string;
        url: string | null;
    } | null;
    defects: OpenDefect[];
};

export type OpenDefects = {
    /** Distinct open defects, counted once even when they hit several requirements. */
    total: number;
    groups: OpenDefectGroup[];
};

export type DashboardProps = {
    stats: DashboardStats;
    statusBreakdown: StatusBreakdownItem[];
    classificationBreakdown: ClassificationBreakdownItem[];
    workload: WorkloadItem[];
    creationTrend: CreationTrendPoint[];
    requirementsCount: number;
    /** Deferred: loaded right after the page, since it asks Jira for fresh statuses. */
    openDefects?: OpenDefects;
};
