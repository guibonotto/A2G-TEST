/**
 * Execution results are stored with their original Portuguese values, so the
 * database and the backend validation rules stay untouched. This maps each
 * stored value to the English label shown in the interface.
 */
const EXECUTION_STATUS_LABELS: Record<string, string> = {
    APROVADO: 'Passed',
    REPROVADO: 'Failed',
    BLOQUEADO: 'Blocked',
    PENDENTE: 'Pending',
};

export function executionStatusLabel(status: string): string {
    return EXECUTION_STATUS_LABELS[status] ?? status;
}
