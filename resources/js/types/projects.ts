export type CurrentProject = {
    id: number;
    uuid: string;
    name: string;
    owner_id: number;
};

export type ProjectSummary = {
    id: number;
    uuid: string;
    name: string;
    isOwner: boolean;
};
