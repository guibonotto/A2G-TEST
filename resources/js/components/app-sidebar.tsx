import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, FolderGit2, LayoutGrid, ShieldCheck, Tags, Users } from 'lucide-react';
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
        title: 'Casos de teste',
        href: testCasesIndex(),
        icon: ClipboardList,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Gerenciar Contas',
        href: accountsIndex(),
        icon: Users,
    },
    {
        title: 'Gerenciar Permissões',
        href: rolePermissionsIndex(),
        icon: ShieldCheck,
    },
    {
        title: 'Gerenciar Status',
        href: testCaseStatusesIndex(),
        icon: Tags,
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
                {isQa && <NavMain items={managementNavItems} label="Gerenciamento" />}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
