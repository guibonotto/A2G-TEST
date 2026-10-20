import type { Auth } from '@/types/auth';
import type { CurrentProject, ProjectSummary } from '@/types/projects';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            currentProject: CurrentProject | null;
            myProjects: ProjectSummary[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
