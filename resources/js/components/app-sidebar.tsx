import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Building2,
    CheckCircle,
    ClipboardCheck,
    ClipboardList,
    CreditCard,
    FileCheck2,
    FileText,
    History,
    Inbox,
    LayoutGrid,
    ListChecks,
    Monitor,
    RotateCcw,
    Send,
    ShieldCheck,
    Tags,
    Users,
    Warehouse,
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
import type { NavItem, SharedData } from '@/types';

export function AppSidebar() {
    const permissions = (usePage<SharedData>().props.auth.permissions ??
        []) as string[];
    const can = (permission: string) => permissions.includes(permission);
    const groups: { title: string; items: NavItem[] }[] = [
        {
            title: 'Dashboard',
            items: [
                can('dashboard.view') && {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                },
            ],
        },
        {
            title: 'Pengajuan Dana',
            items: [
                can('submissions.view') && {
                    title: 'Pengajuan Saya',
                    href: '/submissions',
                    icon: FileText,
                },
                can('finance-submissions.view') && {
                    title: 'Pengajuan Masuk',
                    href: '/finance/submissions',
                    icon: Inbox,
                },
                can('finance-submissions.view-approval-revision') && {
                    title: 'Revisi Approval',
                    href: '/finance/approval-revisions',
                    icon: ClipboardList,
                },
                can('approval-submissions.view-director-revision') && {
                    title: 'Revisi Director',
                    href: '/approval/director-revisions',
                    icon: ClipboardList,
                },
            ],
        },
        {
            title: 'Approval dan Review',
            items: [
                can('approval-submissions.view') && {
                    title: 'Finance Approval',
                    href: '/approval/submissions',
                    icon: CheckCircle,
                },
                can('director-submissions.view') && {
                    title: 'Director Review',
                    href: '/director/submissions',
                    icon: ShieldCheck,
                },
            ],
        },
        {
            title: 'Pencairan dan Distribusi',
            items: [
                can('director-disbursements.view') && {
                    title: 'Pencairan Dana',
                    href: '/director/disbursements',
                    icon: CreditCard,
                },
                can('fund-distributions.view') && {
                    title: 'Distribusi Dana',
                    href: '/finance/fund-distributions',
                    icon: Send,
                },
                can('fund-receipts.confirm') && {
                    title: 'Konfirmasi Penerimaan',
                    href: '/fund-receipts',
                    icon: FileCheck2,
                },
            ],
        },
        {
            title: 'Pertanggungjawaban',
            items: [
                can('accountability-reports.create') && {
                    title: 'Laporan Penggunaan Dana',
                    href: '/accountability-reports',
                    icon: ClipboardList,
                },
                can('advance-settlements.view') && {
                    title: 'Settlement Uang Panjar',
                    href: '/advance-settlements',
                    icon: ClipboardCheck,
                },
                can('fund-returns.create') && {
                    title: 'Pengembalian Sisa Dana',
                    href: '/fund-returns',
                    icon: RotateCcw,
                },
                can('accountability-reports.review') && {
                    title: 'Review Laporan',
                    href: '/finance/accountability-reports',
                    icon: ClipboardCheck,
                },
                can('fund-returns.review') && {
                    title: 'Review Pengembalian',
                    href: '/finance/fund-returns',
                    icon: ClipboardCheck,
                },
                can('accountability-reports.approve') && {
                    title: 'Approval Laporan',
                    href: '/approval/accountability-reports',
                    icon: FileCheck2,
                },
                can('fund-returns.approve') && {
                    title: 'Approval Pengembalian',
                    href: '/approval/fund-returns',
                    icon: FileCheck2,
                },
            ],
        },
        {
            title: 'Monitoring dan Laporan',
            items: [
                can('finance-monitoring.view') && {
                    title: 'Monitoring Finance',
                    href: '/monitoring/finance',
                    icon: Monitor,
                },
                can('approval-monitoring.view') && {
                    title: 'Monitoring Approval',
                    href: '/monitoring/approval',
                    icon: Monitor,
                },
                can('director-monitoring.view') && {
                    title: 'Monitoring Director',
                    href: '/director/monitoring',
                    icon: Monitor,
                },
                can('global-monitoring.view') && {
                    title: 'Monitoring Global',
                    href: '/monitoring/global',
                    icon: Monitor,
                },
                can('fund-monitoring.view') && {
                    title: 'Monitoring Dana',
                    href: '/monitoring/funds',
                    icon: History,
                },
            ],
        },
        {
            title: 'Master Data',
            items: [
                can('users.view') && {
                    title: 'User',
                    href: '/users',
                    icon: Users,
                },
                can('pics.view') && {
                    title: 'PIC KDKMP',
                    href: '/pics',
                    icon: Users,
                },
                can('cooperatives.view') && {
                    title: 'Koperasi',
                    href: '/cooperatives',
                    icon: Warehouse,
                },
                can('bank-accounts.view') && {
                    title: 'Rekening User',
                    href: '/bank-accounts',
                    icon: CreditCard,
                },
                can('company-bank-accounts.view') && {
                    title: 'Rekening Perusahaan',
                    href: '/company-bank-accounts',
                    icon: Building2,
                },
                can('cooperative-bank-accounts.view') && {
                    title: 'Rekening Koperasi',
                    href: '/cooperative-bank-accounts',
                    icon: CreditCard,
                },
                (can('submission-masters.view') ||
                    can('submission-categories.view')) && {
                    title: 'Kategori Pengajuan',
                    href: '/submission-categories',
                    icon: Tags,
                },
                (can('submission-masters.view') ||
                    can('submission-types.view')) && {
                    title: 'Jenis Pengajuan',
                    href: '/submission-types',
                    icon: ListChecks,
                },
            ],
        },
        {
            title: 'Sistem',
            items: [
                can('notifications.view') && {
                    title: 'Notifikasi',
                    href: '/notifications',
                    icon: Bell,
                },
                can('audit-logs.view') && {
                    title: 'Audit Log',
                    href: '/audit-logs',
                    icon: ClipboardList,
                },
            ],
        },
    ]
        .map((group) => ({
            ...group,
            items: group.items.filter(Boolean) as NavItem[],
        }))
        .filter((group) => group.items.length > 0);
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
                {groups.map((group, index) => (
                    <NavMain
                        key={group.title}
                        title={group.title}
                        items={group.items}
                        collapsible={index > 0}
                    />
                ))}
            </SidebarContent>
            <SidebarFooter>
                <NavFooter items={[]} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
