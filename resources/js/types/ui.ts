import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    /** The request that reverts the action, shown as an "Undo" button. */
    undo?: {
        url: string;
        method: 'post' | 'put' | 'patch' | 'delete';
        data: Record<string, unknown>;
    } | null;
    /** A follow-up the toast offers as a button, such as "Create another". */
    link?: {
        url: string;
        label: string;
    } | null;
};

export type AuthLayoutProps = {
    children?: ReactNode;
    name?: string;
    title?: string;
    description?: string;
};
