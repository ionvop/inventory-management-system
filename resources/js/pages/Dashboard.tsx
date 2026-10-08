import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface RecentTransaction {
    id: number;
    type: string;
    type_label: string;
    supplier_name: string | null;
    item_code: string | null;
    quantity: string;
    total_cost: string;
    transaction_date: string;
    profile_name: string | null;
    ward_name: string | null;
    reverses_transaction_id: number | null;
    is_reversed: boolean;
}

interface DashboardSummary {
    period: { year: number; month: number; status: string } | null;
    active_supplier_items: number;
    stock_value: number;
    transactions_this_period: number;
    near_expiry_days: number;
    low_stock_threshold: number;
}

interface DashboardProps {
    alerts: Record<string, number>;
    summary: DashboardSummary;
    recentTransactions: RecentTransaction[];
}

type AlertSeverity = 'danger' | 'warning' | 'info';

interface AlertDefinition {
    label: string;
    reason: string;
    href: string;
    severity: AlertSeverity;
}

/**
 * The alert metrics the dashboard can show, in priority order.
 *
 * The backend decides which keys are present (role scoping); this map supplies
 * the display label, the link to the screen where the action is taken, and a
 * one-line reason explaining why the metric is worth surfacing.
 */
const alertDefinitions: Record<string, AlertDefinition> = {
    expired_batches: {
        label: 'Expired batches',
        reason: 'Expired stock must be pulled out before it can be used.',
        href: '/batches',
        severity: 'danger',
    },
    negative_balances: {
        label: 'Negative balances',
        reason: 'A negative balance blocks period close and signals a data error to correct.',
        href: '/transactions',
        severity: 'danger',
    },
    damaged_batches: {
        label: 'Damaged batches',
        reason: 'Damaged stock is flagged for pull-out and appears in report remarks.',
        href: '/batches',
        severity: 'warning',
    },
    near_expiry_batches: {
        label: 'Near-expiry batches',
        reason: 'Pull out before the expiry date to avoid waste.',
        href: '/batches',
        severity: 'warning',
    },
    low_stock_items: {
        label: 'Low-stock items',
        reason: 'Running low may need a new receipt to avoid stock-outs.',
        href: '/transactions',
        severity: 'warning',
    },
    open_periods_to_close: {
        label: 'Periods ready to close',
        reason: 'A past month is still open; close it to freeze figures and carry balances forward.',
        href: '/periods',
        severity: 'info',
    },
    override_transactions: {
        label: 'Overrides to review',
        reason: 'Negative-balance overrides are high-impact and should be reviewed.',
        href: '/transactions',
        severity: 'info',
    },
    missing_contract_prices: {
        label: 'Missing contract prices',
        reason: 'Items without an active contract price cannot be transacted against.',
        href: '/supplier-items',
        severity: 'info',
    },
};

const severityClasses: Record<AlertSeverity, string> = {
    danger: 'border-destructive/40 bg-destructive/5',
    warning: 'border-amber-500/40 bg-amber-500/5',
    info: 'border-border bg-card',
};

const severityCountClasses: Record<AlertSeverity, string> = {
    danger: 'text-destructive',
    warning: 'text-amber-600 dark:text-amber-400',
    info: 'text-foreground',
};

const monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

export default function Dashboard({
    alerts,
    summary,
    recentTransactions,
}: DashboardProps) {
    const { activeProfile } = usePage().props;

    const periodLabel = summary.period
        ? `${monthNames[summary.period.month - 1]} ${summary.period.year}`
        : 'No period yet';

    // Render the alerts the backend sent, in the priority order above.
    const alertKeys = Object.keys(alertDefinitions).filter(
        (key) => key in alerts,
    );

    const actionCount = alertKeys.reduce(
        (total, key) => total + (alerts[key] ?? 0),
        0,
    );

    return (
        <AppLayout title="Dashboard">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">Dashboard</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Welcome{activeProfile ? `, ${activeProfile.name}` : ''}.
                    {actionCount > 0
                        ? ` ${actionCount} item${actionCount === 1 ? '' : 's'} need attention.`
                        : ' Nothing needs attention right now.'}
                </p>
            </div>

            <section>
                <h2 className="mb-3 text-sm font-semibold text-foreground">
                    Action required
                </h2>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {alertKeys.map((key) => {
                        const definition = alertDefinitions[key];
                        const count = alerts[key] ?? 0;
                        const isClear = count === 0;

                        return (
                            <Link
                                key={key}
                                href={definition.href}
                                className={`rounded-lg border p-4 transition-colors hover:bg-muted ${
                                    isClear
                                        ? 'border-border bg-card'
                                        : severityClasses[definition.severity]
                                }`}
                            >
                                <div className="flex items-baseline justify-between gap-2">
                                    <p className="text-xs text-muted-foreground uppercase">
                                        {definition.label}
                                    </p>
                                    <p
                                        className={`text-2xl font-bold ${
                                            isClear
                                                ? 'text-muted-foreground'
                                                : severityCountClasses[
                                                      definition.severity
                                                  ]
                                        }`}
                                    >
                                        {count}
                                    </p>
                                </div>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {definition.reason}
                                </p>
                            </Link>
                        );
                    })}
                </div>
            </section>

            <section className="mt-8">
                <h2 className="mb-3 text-sm font-semibold text-foreground">
                    At a glance
                </h2>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground uppercase">
                            Open period
                        </p>
                        <p className="mt-1 text-lg font-bold text-foreground">
                            {periodLabel}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            All balances and movements are scoped to the current
                            month.
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground uppercase">
                            Stock value
                        </p>
                        <p className="mt-1 text-lg font-bold text-foreground">
                            {summary.stock_value.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Total cost of stock on hand in the open period.
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground uppercase">
                            Active supplier items
                        </p>
                        <p className="mt-1 text-lg font-bold text-foreground">
                            {summary.active_supplier_items}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Number of supplier items currently transactable.
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground uppercase">
                            Movements this period
                        </p>
                        <p className="mt-1 text-lg font-bold text-foreground">
                            {summary.transactions_this_period}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Volume of movements recorded this month.
                        </p>
                    </div>
                </div>
            </section>

            <section className="mt-8">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-sm font-semibold text-foreground">
                        Recent activity
                    </h2>
                    <Link
                        href="/transactions"
                        className="text-xs text-primary hover:underline"
                    >
                        View all transactions
                    </Link>
                </div>
                <div className="overflow-hidden rounded-lg border border-border bg-card">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-4 py-3 font-medium">Date</th>
                                <th className="px-4 py-3 font-medium">Type</th>
                                <th className="px-4 py-3 font-medium">
                                    Supplier item
                                </th>
                                <th className="px-4 py-3 font-medium">Qty</th>
                                <th className="px-4 py-3 font-medium">
                                    Total cost
                                </th>
                                <th className="px-4 py-3 font-medium">Ward</th>
                                <th className="px-4 py-3 font-medium">
                                    Recorded by
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentTransactions.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-sm text-muted-foreground"
                                    >
                                        No transactions recorded yet.
                                    </td>
                                </tr>
                            )}
                            {recentTransactions.map((transaction) => (
                                <tr
                                    key={transaction.id}
                                    className="border-b border-border last:border-0"
                                >
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {transaction.transaction_date}
                                    </td>
                                    <td className="px-4 py-3 text-foreground">
                                        {transaction.type_label}
                                        {transaction.reverses_transaction_id !==
                                        null ? (
                                            <span className="ml-1 text-xs text-muted-foreground">
                                                (reversal)
                                            </span>
                                        ) : transaction.is_reversed ? (
                                            <span className="ml-1 text-xs text-muted-foreground">
                                                (reversed)
                                            </span>
                                        ) : null}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {transaction.supplier_name ?? '—'} —{' '}
                                        {transaction.item_code ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-foreground">
                                        {transaction.quantity}
                                    </td>
                                    <td className="px-4 py-3 text-foreground">
                                        {transaction.total_cost}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {transaction.ward_name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {transaction.profile_name ?? '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        </AppLayout>
    );
}
