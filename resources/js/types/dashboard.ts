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

export type DashboardProps = {
    stats: DashboardStats;
    statusBreakdown: StatusBreakdownItem[];
    classificationBreakdown: ClassificationBreakdownItem[];
    workload: WorkloadItem[];
    creationTrend: CreationTrendPoint[];
    requirementsCount: number;
};
