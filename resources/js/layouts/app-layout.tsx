import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
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
    /** Only shown to profiles holding one of these roles. */
    roles?: string[];
}

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Transactions', href: '/transactions' },
    { label: 'Suppliers', href: '/suppliers', roles: ['administrator'] },
    { label: 'Items', href: '/items', roles: ['administrator'] },
    {
        label: 'Supplier Items',
        href: '/supplier-items',
        roles: ['administrator'],
    },
];

export default function AppLayout({ title, children }: AppLayoutProps) {
    const { activeProfile } = usePage().props;
    const currentUrl = usePage().url;

    const visibleNavItems = navItems.filter(
        (item) =>
            !item.roles ||
            (activeProfile && item.roles.includes(activeProfile.role)),
    );

    return (
        <>
            <Head title={title} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="border-b border-border bg-card">
                    <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-3">
                        <div className="flex items-center gap-6">
                            <span className="text-sm font-semibold text-foreground">
                                Inventory Manager
                            </span>
                            <nav className="flex items-center gap-1">
                                {visibleNavItems.map((item) => {
                                    const isActive =
                                        currentUrl === item.href ||
                                        currentUrl.startsWith(`${item.href}/`);

                                    return (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            className={cn(
                                                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                                isActive
                                                    ? 'bg-muted text-foreground'
                                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                            )}
                                        >
                                            {item.label}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>

                        <div className="flex items-center gap-3">
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
        </>
    );
}
