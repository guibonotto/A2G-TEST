import type { TestCaseStatusColor } from '@/types/test-cases';

export type StatusBreakdownItem = {
    id: number;
    name: string;
    color: TestCaseStatusColor;
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
};