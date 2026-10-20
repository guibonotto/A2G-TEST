import type { TestCaseStatusColor } from '@/types/test-cases';

const STATUS_COLOR_VARS: Record<TestCaseStatusColor, string> = {
    success: 'var(--success)',
    destructive: 'var(--destructive)',
    warning: 'var(--warning)',
    info: 'var(--info)',
    secondary: 'var(--secondary-foreground)',
    blue: '#3b82f6',
    purple: '#8b5cf6',
    orange: '#f97316',
    pink: '#ec4899',
};

export function statusColorVar(color: TestCaseStatusColor): string {
    return STATUS_COLOR_VARS[color] ?? 'var(--muted-foreground)';
}