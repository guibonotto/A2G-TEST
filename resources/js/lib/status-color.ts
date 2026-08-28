import type { TestCaseStatusColor } from '@/types/test-cases';

const STATUS_COLOR_VARS: Record<TestCaseStatusColor, string> = {
    success: 'var(--success)',
    destructive: 'var(--destructive)',
    warning: 'var(--warning)',
    info: 'var(--info)',
    secondary: 'var(--secondary-foreground)',
};

export function statusColorVar(color: TestCaseStatusColor): string {
    return STATUS_COLOR_VARS[color] ?? 'var(--muted-foreground)';
}