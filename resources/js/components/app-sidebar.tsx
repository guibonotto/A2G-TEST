import { Form, Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    ClipboardList,
    ChevronDown,
    FileText,
    Folder,
    LayoutGrid,
    Plug,
    ShieldCheck,
    Tags,
    UserCheck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import { index as accountsIndex } from '@/routes/accounts';
import { edit as jiraEdit } from '@/routes/jira';
import { index as projectsIndex } from '@/routes/projects';
import { show as projectsShow } from '@/routes/projects';
import { index as requirementsIndex } from '@/routes/requirements';
import { index as rolePermissionsIndex } from '@/routes/role-permissions';
import { index as testCaseStatusesIndex } from '@/routes/test-case-statuses';
import {
    assigned as testCasesAssigned,
    index as testCasesIndex,
} from '@/routes/test-cases';
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
        icon: Briefcase,
    },
    {
        title: 'Test cases',
        href: testCasesIndex(),
        icon: ClipboardList,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Manage accounts',
        href: accountsIndex(),
        icon: Users,
        permission: 'users.manage',
    },
    {
        title: 'Manage permissions',
        href: rolePermissionsIndex(),
        icon: ShieldCheck,
        permission: 'roles.manage',
    },
    {
        title: 'Manage statuses',
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
        title: 'Jira integration',
        href: jiraEdit(),
        icon: Plug,
    },
];

export function AppSidebar() {
    const { auth, currentProject, myProjects, assignedToMeCount } =
        usePage().props;
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
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <SidebarMenuButton
                                        size="lg"
                                        tooltip="Switch project"
                                    >
                                        <Folder className="text-primary" />
                                        <span className="min-w-0 flex-1 truncate font-semibold">
                                            {currentProject.name}
                                        </span>
                                        <ChevronDown className="ml-auto size-4 shrink-0 text-muted-foreground" />
                                    </SidebarMenuButton>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="start"
                                    side="right"
                                    className="w-64"
                                >
                                    <DropdownMenuLabel>
                                        Switch project
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    {myProjects.map((project) => (
                                        <Form
                                            key={project.id}
                                            {...ProjectController.select.form(
                                                project.uuid,
                                            )}
                                        >
                                            <button
                                                type="submit"
                                                className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm outline-none hover:bg-accent"
                                            >
                                                <Folder className="size-4 text-muted-foreground" />
                                                <span className="min-w-0 flex-1 truncate">
                                                    {project.name}
                                                </span>
                                                {project.id ===
                                                    currentProject.id && (
                                                    <span className="text-xs text-primary">
                                                        Current
                                                    </span>
                                                )}
                                            </button>
                                        </Form>
                                    ))}
                                    <DropdownMenuSeparator />
                                    <Link
                                        href={projectsIndex()}
                                        className="block rounded-sm px-2 py-2 text-sm hover:bg-accent"
                                    >
                                        Manage projects
                                    </Link>
                                    {currentProject.owner_id === auth.user.id && (
                                        <Link
                                            href={projectsShow(currentProject.uuid)}
                                            className="block rounded-sm px-2 py-2 text-sm hover:bg-accent"
                                        >
                                            Manage access
                                        </Link>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </SidebarMenuItem>
                    )}
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={[
                        ...mainNavItems,
                        {
                            title: 'Assigned to me',
                            href: testCasesAssigned(),
                            icon: UserCheck,
                            badge: assignedToMeCount,
                        },
                        {
                            title: 'Manage requirements',
                            href: requirementsIndex(),
                            icon: FileText,
                        },
                    ]}
                />
                {canManage && allowedManagementNavItems.length > 0 && (
                    <NavMain
                        items={allowedManagementNavItems}
                        label="Management"
                    />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
