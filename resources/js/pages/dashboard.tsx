import { Head } from '@inertiajs/react';
import { CalendarPlus, FileCheck2, ListTodo, UserX } from 'lucide-react';
import { ClassificationChart } from '@/components/dashboard/classification-chart';
import { CreationTrendChart } from '@/components/dashboard/creation-trend-chart';
import { StatCard } from '@/components/dashboard/stat-card';
import { StatusDistributionChart } from '@/components/dashboard/status-distribution-chart';
import { WorkloadChart } from '@/components/dashboard/workload-chart';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types/dashboard';

export default function Dashboard({ stats, statusBreakdown, classificationBreakdown, workload, creationTrend }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Total de Casos de Teste" value={stats.total} icon={FileCheck2} />
                    <StatCard label="Sem Responsável" value={stats.unassigned} icon={UserX} />
                    <StatCard label="Criados (7 dias)" value={stats.createdLast7Days} icon={CalendarPlus} />
                    <StatCard label="Status em Uso" value={stats.statusesInUse} icon={ListTodo} />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <StatusDistributionChart data={statusBreakdown} />
                    <CreationTrendChart data={creationTrend} />
                    <ClassificationChart data={classificationBreakdown} />
                    <WorkloadChart data={workload} />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};