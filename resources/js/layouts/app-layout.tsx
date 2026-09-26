import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    CalendarClock,
    LayoutDashboard,
    Menu,
    Package,
    PackageSearch,
    Tags,
    Truck,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import AppearanceToggle from '@/components/appearance-toggle';
import { roleLabels } from '@/lib/profile-roles';
import { cn } from '@/lib/utils';

interface AppLayoutProps {
    title: string;
    children: ReactNode;
}

interface NavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Only shown to profiles holding one of these roles. */
    roles?: string[];
}

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Transactions', href: '/transactions', icon: ArrowLeftRight },
    { label: 'Batches', href: '/batches', icon: PackageSearch },
    {
        label: 'Periods',
        href: '/periods',
        icon: CalendarClock,
        roles: ['supervisor', 'administrator'],
    },
    {
        label: 'Suppliers',
        href: '/suppliers',
        icon: Truck,
        roles: ['administrator'],
    },
    {
        label: 'Items',
        href: '/items',
        icon: Package,
        roles: ['administrator'],
    },
    {
        label: 'Supplier Items',
        href: '/supplier-items',
        icon: Tags,
        roles: ['administrator'],
    },
];

export default function AppLayout({ title, children }: AppLayoutProps) {
    const { activeProfile } = usePage().props;
    const currentUrl = usePage().url;
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);

    const visibleNavItems = navItems.filter(
        (item) =>
            !item.roles ||
            (activeProfile && item.roles.includes(activeProfile.role)),
    );

    // Close the mobile drawer when the route changes.
    useEffect(() => {
        setIsSidebarOpen(false);
    }, [currentUrl]);

    // Close the mobile drawer on Escape.
    useEffect(() => {
        if (!isSidebarOpen) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsSidebarOpen(false);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [isSidebarOpen]);

    return (
        <>
            <Head title={title} />
            <div className="flex min-h-screen bg-background text-foreground">
                {/* Mobile overlay */}
                {isSidebarOpen && (
                    <div
                        className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                        aria-hidden="true"
                        onClick={() => setIsSidebarOpen(false)}
                    />
                )}

                {/* Sidebar */}
                <aside
                    id="app-sidebar"
                    className={cn(
                        'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-sidebar-border bg-sidebar-background text-sidebar-foreground transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0',
                        isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
                    )}
                >
                    <div className="flex h-14 items-center justify-between border-b border-sidebar-border px-4">
                        <span className="text-sm font-semibold text-sidebar-foreground">
                            Inventory Manager
                        </span>
                        <button
                            type="button"
                            onClick={() => setIsSidebarOpen(false)}
                            className="rounded-md p-1.5 text-sidebar-foreground hover:bg-sidebar-accent lg:hidden"
                            aria-label="Close navigation"
                        >
                            <X className="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    <nav className="flex flex-1 flex-col gap-1 overflow-y-auto p-3">
                        {visibleNavItems.map((item) => {
                            const isActive =
                                currentUrl === item.href ||
                                currentUrl.startsWith(`${item.href}/`);
                            const Icon = item.icon;

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={cn(
                                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                        isActive
                                            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                            : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground',
                                    )}
                                >
                                    <Icon
                                        className="h-4 w-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                {/* Content column */}
                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-border bg-card">
                        <div className="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                            <button
                                type="button"
                                onClick={() => setIsSidebarOpen(true)}
                                className="rounded-md border border-border bg-card p-2 text-foreground hover:bg-muted lg:hidden"
                                aria-label="Open navigation"
                                aria-controls="app-sidebar"
                                aria-expanded={isSidebarOpen}
                            >
                                <Menu className="h-4 w-4" aria-hidden="true" />
                            </button>

                            <div className="flex flex-1 items-center justify-end gap-3">
                                <AppearanceToggle />
                                {activeProfile && (
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-foreground">
                                            {activeProfile.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {roleLabels[activeProfile.role]}
                                        </p>
                                    </div>
                                )}
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground hover:bg-muted"
                                >
                                    Switch profile
                                </Link>
                            </div>
                        </div>
                    </header>

                    <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-8">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}
