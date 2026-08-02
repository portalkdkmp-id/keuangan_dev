import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items = [], title = 'Menu', collapsible = false }: { items: NavItem[]; title?: string; collapsible?: boolean }) {
    const { isCurrentUrl } = useCurrentUrl();

    const content = (
        <SidebarGroup className="px-2 py-0">
            {/* <SidebarGroupLabel>{title}</SidebarGroupLabel> */}
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );

    if (!collapsible) return content;

    return <Collapsible defaultOpen className="group/nav-group"><CollapsibleTrigger className="flex w-full items-center justify-between px-4 py-2 text-xs font-medium text-sidebar-foreground/70"><span>{title}</span><ChevronDown className="size-3 transition-transform group-data-[state=closed]/nav-group:-rotate-90" /></CollapsibleTrigger><CollapsibleContent>{content}</CollapsibleContent></Collapsible>;
}
