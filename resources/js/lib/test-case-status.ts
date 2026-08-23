import type { TestCaseStatus } from '@/types';

export function testCaseStatusBadgeVariant(
    status: TestCaseStatus,
): 'success' | 'destructive' | 'warning' | 'secondary' | 'info' {
    switch (status) {
        case 'APROVADO':
            return 'success';
        case 'REPROVADO':
            return 'destructive';
        case 'PENDENTE':
            return 'warning';
        case 'CANCELADO':
            return 'secondary';
        case 'REGRESSÃO':
            return 'info';
    }
}
