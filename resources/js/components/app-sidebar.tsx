import { Link, usePage } from '@inertiajs/react';
import { Bell, CheckCircle, ClipboardList, CreditCard, FileText, Inbox, LayoutGrid, ListChecks, Tags, Users, Warehouse } from 'lucide-react';
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
import type { NavItem, SharedData } from '@/types';

export function AppSidebar() {
    const permissions = (usePage<SharedData>().props.auth.permissions ?? []) as string[];
    const can = (permission: string) => permissions.includes(permission);
    const mainNavItems: NavItem[] = [
        can('dashboard.view') && { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        can('users.view') && { title: 'Users', href: '/users', icon: Users },
        can('cooperatives.view') && { title: 'Cooperatives', href: '/cooperatives', icon: Warehouse },
        can('submissions.view') && { title: 'Pengajuan Dana', href: '/submissions', icon: FileText },
        can('finance-submissions.view') && { title: 'Pengajuan Masuk', href: '/finance/submissions', icon: Inbox },
        can('approval-submissions.view') && { title: 'Approval Keuangan', href: '/approval/submissions', icon: CheckCircle },
        can('bank-accounts.view') && { title: 'Rekening', href: '/bank-accounts', icon: CreditCard },
        can('submission-masters.view') && { title: 'Kategori Pengajuan', href: '/submission-categories', icon: Tags },
        can('submission-masters.view') && { title: 'Jenis Pengajuan', href: '/submission-types', icon: ListChecks },
        can('notifications.view') && { title: 'Notifications', href: '/notifications', icon: Bell },
        can('audit-logs.view') && { title: 'Audit Logs', href: '/audit-logs', icon: ClipboardList },
    ].filter(Boolean) as NavItem[];

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
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={[]} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
