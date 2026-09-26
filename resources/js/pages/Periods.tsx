import { Form } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';

interface PeriodRow {
    id: number;
    year: number;
    month: number;
    status: 'open' | 'closed';
    closed_at: string | null;
    closed_by: string | null;
    negative_count: number;
    transaction_count: number;
}

interface PeriodsProps {
    periods: PeriodRow[];
    canReopen: boolean;
}

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

export default function Periods({ periods, canReopen }: PeriodsProps) {
    const [reopening, setReopening] = useState<PeriodRow | null>(null);

    return (
        <AppLayout title="Periods">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">Periods</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Close a month to freeze its transactions and carry each
                    item&apos;s ending balance forward as the next month&apos;s
                    beginning balance. A period with a negative balance cannot
                    be closed.
                </p>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Period</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">
                                Transactions
                            </th>
                            <th className="px-4 py-3 font-medium">
                                Negative items
                            </th>
                            <th className="px-4 py-3 font-medium">
                                Closed by
                            </th>
                            <th className="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {periods.length === 0 && (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No periods yet. A period is created
                                    automatically when the first transaction for
                                    a month is recorded.
                                </td>
                            </tr>
                        )}
                        {periods.map((period) => (
                            <tr
                                key={period.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {monthNames[period.month - 1]} {period.year}
                                </td>
                                <td className="px-4 py-3">
                                    <span
                                        className={
                                            period.status === 'closed'
                                                ? 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                                                : 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                                        }
                                    >
                                        {period.status === 'closed'
                                            ? 'Closed'
                                            : 'Open'}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {period.transaction_count}
                                </td>
                                <td className="px-4 py-3">
                                    {period.negative_count > 0 ? (
                                        <span className="text-destructive">
                                            {period.negative_count}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            0
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {period.closed_by ?? '—'}
                                    {period.closed_at
                                        ? ` · ${period.closed_at}`
                                        : ''}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        {period.status === 'open' && (
                                            <Form
                                                action={`/periods/${period.id}/close`}
                                                method="post"
                                            >
                                                {({ processing, errors }) => (
                                                    <div className="flex flex-col items-end gap-1">
                                                        <button
                                                            type="submit"
                                                            disabled={
                                                                processing ||
                                                                period.negative_count >
                                                                    0
                                                            }
                                                            title={
                                                                period.negative_count >
                                                                0
                                                                    ? 'Correct the negative balances before closing.'
                                                                    : undefined
                                                            }
                                                            className="rounded-md border border-border px-2 py-1 text-xs text-foreground hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                                                        >
                                                            {processing
                                                                ? 'Closing...'
                                                                : 'Close'}
                                                        </button>
                                                        {errors.period && (
                                                            <p className="max-w-xs text-right text-xs text-destructive">
                                                                {errors.period}
                                                            </p>
                                                        )}
                                                    </div>
                                                )}
                                            </Form>
                                        )}
                                        {period.status === 'closed' &&
                                            canReopen && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setReopening(period)
                                                    }
                                                    className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                                >
                                                    Reopen
                                                </button>
                                            )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {reopening && (
                <div className="mt-4 rounded-lg border border-border bg-card p-4">
                    <h2 className="text-sm font-semibold text-foreground">
                        Reopen {monthNames[reopening.month - 1]}{' '}
                        {reopening.year}
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Reopening allows further transactions in this period. A
                        reason is required and will be logged.
                    </p>
                    <Form
                        action={`/periods/${reopening.id}/reopen`}
                        method="post"
                        onSuccess={() => setReopening(null)}
                        className="mt-3"
                    >
                        {({ errors, processing }) => (
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
                                <div className="flex-1">
                                    <input
                                        type="text"
                                        name="reopened_reason"
                                        placeholder="Reason for reopening"
                                        className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                                    />
                                    {errors.reopened_reason && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.reopened_reason}
                                        </p>
                                    )}
                                    {errors.period && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.period}
                                        </p>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                    >
                                        {processing
                                            ? 'Reopening...'
                                            : 'Reopen period'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setReopening(null)}
                                        className="rounded-md border border-border px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            )}
        </AppLayout>
    );
}
