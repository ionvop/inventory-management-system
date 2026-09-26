import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function Dashboard() {
    const { activeProfile } = usePage().props;
    const isAdministrator = activeProfile?.role === 'administrator';

    return (
        <AppLayout title="Dashboard">
            <h1 className="text-xl font-bold text-foreground">Dashboard</h1>
            <p className="mt-2 text-sm text-muted-foreground">
                Welcome{activeProfile ? `, ${activeProfile.name}` : ''}. The
                workspace is ready for the next increment.
            </p>

            <div className="mt-8 grid gap-4 sm:grid-cols-2">
                <Link
                    href="/transactions"
                    className="rounded-lg border border-border bg-card p-4 transition-colors hover:bg-muted"
                >
                    <h2 className="text-sm font-semibold text-foreground">
                        Transactions
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Record stock in/out movements and view live balances.
                    </p>
                </Link>
            </div>

            {isAdministrator && (
                <div className="mt-8 grid gap-4 sm:grid-cols-2">
                    <Link
                        href="/suppliers"
                        className="rounded-lg border border-border bg-card p-4 transition-colors hover:bg-muted"
                    >
                        <h2 className="text-sm font-semibold text-foreground">
                            Suppliers
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Manage contracted vendors and their contract status.
                        </p>
                    </Link>
                    <Link
                        href="/items"
                        className="rounded-lg border border-border bg-card p-4 transition-colors hover:bg-muted"
                    >
                        <h2 className="text-sm font-semibold text-foreground">
                            Items
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Manage the nutrition formula item catalog.
                        </p>
                    </Link>
                    <Link
                        href="/supplier-items"
                        className="rounded-lg border border-border bg-card p-4 transition-colors hover:bg-muted"
                    >
                        <h2 className="text-sm font-semibold text-foreground">
                            Supplier Items
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Set contract prices linking suppliers to items.
                        </p>
                    </Link>
                </div>
            )}
        </AppLayout>
    );
}
