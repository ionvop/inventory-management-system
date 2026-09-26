import AppLayout from '@/layouts/app-layout';

type BatchStatus = 'active' | 'near_expiry' | 'expired' | 'damaged';

interface BatchRow {
    id: number;
    batch_number: string;
    expiration_date: string;
    days_until_expiry: number;
    status: BatchStatus;
    supplier_name: string | null;
    item_code: string | null;
    item_description: string | null;
    unit: string | null;
}

interface BatchesProps {
    batches: BatchRow[];
    counts: Record<BatchStatus, number>;
    nearExpiryDays: number;
}

const statusLabels: Record<BatchStatus, string> = {
    expired: 'Expired',
    near_expiry: 'Near expiry',
    active: 'Active',
    damaged: 'Damaged',
};

const statusClasses: Record<BatchStatus, string> = {
    expired:
        'rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive',
    near_expiry:
        'rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400',
    active: 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400',
    damaged:
        'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground',
};

const summaryOrder: BatchStatus[] = [
    'expired',
    'near_expiry',
    'active',
    'damaged',
];

function daysLabel(days: number): string {
    if (days < 0) {
        return `${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'} ago`;
    }

    if (days === 0) {
        return 'Today';
    }

    return `in ${days} day${days === 1 ? '' : 's'}`;
}

export default function Batches({
    batches,
    counts,
    nearExpiryDays,
}: BatchesProps) {
    return (
        <AppLayout title="Batches">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">
                    Batch expiry
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Batches are flagged automatically as near expiry within{' '}
                    {nearExpiryDays} days of their expiration date, so stock can
                    be pulled out before it lapses.
                </p>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {summaryOrder.map((status) => (
                    <div
                        key={status}
                        className="rounded-lg border border-border bg-card p-4"
                    >
                        <p className="text-xs text-muted-foreground uppercase">
                            {statusLabels[status]}
                        </p>
                        <p className="mt-1 text-2xl font-bold text-foreground">
                            {counts[status] ?? 0}
                        </p>
                    </div>
                ))}
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Item</th>
                            <th className="px-4 py-3 font-medium">Supplier</th>
                            <th className="px-4 py-3 font-medium">Batch no.</th>
                            <th className="px-4 py-3 font-medium">Expires</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {batches.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No batches yet. A batch is created when
                                    stock is received.
                                </td>
                            </tr>
                        )}
                        {batches.map((batch) => (
                            <tr
                                key={batch.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {batch.item_code ?? '—'}
                                    {batch.item_description
                                        ? ` — ${batch.item_description}`
                                        : ''}
                                    {batch.unit ? (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            ({batch.unit})
                                        </span>
                                    ) : null}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {batch.supplier_name ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {batch.batch_number}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {batch.expiration_date}
                                    <span className="ml-1 text-xs">
                                        ({daysLabel(batch.days_until_expiry)})
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span
                                        className={statusClasses[batch.status]}
                                    >
                                        {statusLabels[batch.status]}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
