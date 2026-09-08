import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, FileText, FolderGit2, LayoutGrid, Plug, ShieldCheck, Tags, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { edit as jiraEdit } from '@/routes/jira';
import { index as requirementsIndex } from '@/routes/requirements';
import { index as rolePermissionsIndex } from '@/routes/role-permissions';
import { index as testCaseStatusesIndex } from '@/routes/test-case-statuses';
import { index as testCasesIndex } from '@/routes/test-cases';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Test Cases',
        href: testCasesIndex(),
        icon: ClipboardList,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Manage Accounts',
        href: accountsIndex(),
        icon: Users,
    },
    {
        title: 'Manage Permissions',
        href: rolePermissionsIndex(),
        icon: ShieldCheck,
    },
    {
        title: 'Manage Statuses',
        href: testCaseStatusesIndex(),
        icon: Tags,
    },
    {
        title: 'Manage Requirements',
        href: requirementsIndex(),
        icon: FileText,
    },
    {
        title: 'Jira Integration',
        href: jiraEdit(),
        icon: Plug,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isQa = auth.user.role?.slug === 'qa';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {isQa && <NavMain items={managementNavItems} label="Management" />}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
