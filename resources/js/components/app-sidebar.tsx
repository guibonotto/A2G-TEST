import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    ClipboardList,
    FileText,
    FolderGit2,
    LayoutGrid,
    LayoutTemplate,
    Plug,
    ShieldCheck,
    Tags,
    Users,
} from 'lucide-react';
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
import { index as projectsIndex } from '@/routes/projects';
import { index as requirementsIndex } from '@/routes/requirements';
import { index as rolePermissionsIndex } from '@/routes/role-permissions';
import { index as testCaseStatusesIndex } from '@/routes/test-case-statuses';
import { index as testCasesIndex } from '@/routes/test-cases';
// import { index as testTemplatesIndex } from '@/routes/test-templates';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Projects',
        href: projectsIndex(),
        icon: Users,
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
        permission: 'users.manage',
    },
    {
        title: 'Manage Permissions',
        href: rolePermissionsIndex(),
        icon: ShieldCheck,
        permission: 'roles.manage',
    },
    {
        title: 'Manage Statuses',
        href: testCaseStatusesIndex(),
        icon: Tags,
        permission: 'statuses.manage',
    },
    // {
    //     title: 'Manage Templates',
    //     href: testTemplatesIndex(),
    //     icon: LayoutTemplate,
    // },
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
    const { auth, currentProject } = usePage().props;
    const canManage = ['qa', 'admin'].includes(auth.user.role?.slug ?? '');
    const permissions = auth.user.role?.effective_permissions ?? [];
    const allowedManagementNavItems = managementNavItems.filter(
        (item) => !item.permission || permissions.includes(item.permission),
    );

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
                    {currentProject && (
                        <SidebarMenuItem>
                            <SidebarMenuButton size="sm" asChild>
                                <Link
                                    href={projectsIndex()}
                                    className="text-muted-foreground"
                                >
                                    <Users />
                                    <span className="truncate">
                                        {currentProject.name}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    )}
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {canManage && allowedManagementNavItems.length > 0 && (
                    <NavMain
                        items={allowedManagementNavItems}
                        label="Management"
                    />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
