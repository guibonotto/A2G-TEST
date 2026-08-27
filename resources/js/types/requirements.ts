export type RequirementType = 'funcional' | 'nao_funcional';
export type RequirementPriority = 'baixa' | 'media' | 'alta';
export type RequirementStatus = 'pendente' | 'em_andamento' | 'concluido';

export type Requirement = {
    id: number;
    code: string;
    type: RequirementType;
    title: string;
    description: string | null;
    priority: RequirementPriority | null;
    status: RequirementStatus | null;
    created_by: number | null;
};

export type RequirementListItem = Requirement & {
    creator: { id: number; name: string } | null;
    test_cases_count: number;
};

export type RequirementOption = {
    id: number;
    code: string;
    title: string;
};
