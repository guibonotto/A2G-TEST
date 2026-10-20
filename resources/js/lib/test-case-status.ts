export const TEST_CASE_STATUS_TRANSLATIONS: Record<string, string> = {
    Draft: 'Rascunho',
    Obsolete: 'Obsoleto',
    Ready: 'Pronto',
};

export function testCaseStatusLabel(status: string): string {
    return status;
}