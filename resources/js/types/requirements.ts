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
    created_by: number | null;
};

export type RequirementListItem = Requirement & {
    creator: { id: number; name: string } | null;
    test_cases_count: number;
    /** Derived from coverage: linked test cases and their latest runs. */
    status: RequirementStatus;
};

export type RequirementOption = {
    id: number;
    code: string;
    title: string;
    description: string | null;
    /** Test cases already linked to this requirement, across the project. */
    test_cases_count: number;
    /** Link to the Jira issue; null when the requirement was created here. */
    jira_url: string | null;
};
