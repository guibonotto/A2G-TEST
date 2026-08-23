import type { TestCaseStatus } from '@/types';

export function testCaseStatusBadgeVariant(
    status: TestCaseStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'APROVADO':
            return 'default';
        case 'REPROVADO':
        case 'CANCELADO':
            return 'destructive';
        case 'REGRESSÃO':
            return 'outline';
        case 'PENDENTE':
        default:
            return 'secondary';
    }
}
