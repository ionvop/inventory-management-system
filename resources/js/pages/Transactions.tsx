import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import TransactionForm, {
    type SupplierItemOption,
    type TransactionTypeOption,
    type WardOption,
} from '@/components/transaction-form';
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

interface TransactionsProps {
    supplierItems: SupplierItemOption[];
    wards: WardOption[];
    types: TransactionTypeOption[];
    period: { year: number; month: number } | null;
    recentTransactions: RecentTransaction[];
    canOverride: boolean;
    canReverse: boolean;
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

export default function Transactions({
    supplierItems,
    wards,
    types,
    period,
    recentTransactions,
    canOverride,
    canReverse,
}: TransactionsProps) {
    const periodLabel = period
        ? `${monthNames[period.month - 1]} ${period.year}`
        : 'No period yet';

    const [reversing, setReversing] = useState<RecentTransaction | null>(null);

    return (
        <AppLayout title="Transactions">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">
                    Transactions
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Record stock in/out movements. Balances are derived from the
                    transaction log for the open period ({periodLabel}).
                </p>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Supplier</th>
                            <th className="px-4 py-3 font-medium">Item</th>
                            <th className="px-4 py-3 font-medium">Unit</th>
                            <th className="px-4 py-3 font-medium">
                                Contract price
                            </th>
                            <th className="px-4 py-3 font-medium">
                                Balance (qty)
                            </th>
                            <th className="px-4 py-3 font-medium">
                                Balance (cost)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {supplierItems.length === 0 && (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No active supplier items. Add a{' '}
                                    <Link
                                        href="/supplier-items"
                                        className="text-primary hover:underline"
                                    >
                                        contract price
                                    </Link>{' '}
                                    first.
                                </td>
                            </tr>
                        )}
                        {supplierItems.map((supplierItem) => (
                            <tr
                                key={supplierItem.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {supplierItem.supplier_name ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    <Link
                                        href={`/stock/${supplierItem.id}`}
                                        className="text-primary hover:underline"
                                    >
                                        {supplierItem.item_code ?? '—'}
                                    </Link>
                                    {supplierItem.item_description
                                        ? ` — ${supplierItem.item_description}`
                                        : ''}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {supplierItem.unit ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-foreground">
                                    {supplierItem.price}
                                </td>
                                <td
                                    className={
                                        supplierItem.balance.quantity < 0
                                            ? 'px-4 py-3 font-medium text-destructive'
                                            : 'px-4 py-3 text-foreground'
                                    }
                                >
                                    {supplierItem.balance.quantity}
                                </td>
                                <td className="px-4 py-3 text-foreground">
                                    {supplierItem.balance.total_cost}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-8 rounded-lg border border-border bg-card p-4">
                <h2 className="mb-3 text-sm font-semibold text-foreground">
                    Record a movement
                </h2>
                {supplierItems.length > 0 ? (
                    <TransactionForm
                        supplierItems={supplierItems}
                        wards={wards}
                        types={types}
                        canOverride={canOverride}
                    />
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Add at least one active supplier item before recording a
                        movement.
                    </p>
                )}
            </div>

            <div className="mt-8">
                <h2 className="mb-3 text-sm font-semibold text-foreground">
                    Recent transactions
                </h2>
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
                                {canReverse && (
                                    <th className="px-4 py-3 font-medium"></th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {recentTransactions.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canReverse ? 8 : 7}
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
                                    {canReverse && (
                                        <td className="px-4 py-3 text-right">
                                            {transaction.reverses_transaction_id !==
                                            null ? (
                                                <span className="text-xs text-muted-foreground">
                                                    Reversal
                                                </span>
                                            ) : transaction.is_reversed ? (
                                                <span className="text-xs text-muted-foreground">
                                                    Reversed
                                                </span>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setReversing(transaction)
                                                    }
                                                    className="text-xs font-medium text-destructive hover:underline"
                                                >
                                                    Reverse
                                                </button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {reversing && (
                <ReverseDialog
                    transaction={reversing}
                    onClose={() => setReversing(null)}
                />
            )}
        </AppLayout>
    );
}

interface ReverseDialogProps {
    transaction: RecentTransaction;
    onClose: () => void;
}

function ReverseDialog({ transaction, onClose }: ReverseDialogProps) {
    const form = useForm({ remark: '', override_reason: '' });
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(`/transactions/${transaction.id}/reverse`, {
            onSuccess: () => onClose(),
        });
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Reverse transaction"
        >
            <form
                onSubmit={submit}
                className="w-full max-w-md rounded-lg border border-border bg-card p-5"
            >
                <h2 className="text-sm font-semibold text-foreground">
                    Reverse transaction
                </h2>
                <p className="mt-1 text-xs text-muted-foreground">
                    This posts a new reversing transaction dated today that
                    cancels {transaction.type_label.toLowerCase()} of{' '}
                    {transaction.quantity} for{' '}
                    {transaction.item_code ?? 'this item'}. The original is kept
                    for the audit trail.
                </p>

                <div className="mt-4">
                    <label className="mb-1 block text-xs text-muted-foreground">
                        Reason
                    </label>
                    <input
                        type="text"
                        value={form.data.remark}
                        onChange={(event) =>
                            form.setData('remark', event.target.value)
                        }
                        className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                    />
                    {form.errors.remark && (
                        <p className="mt-1 text-xs text-destructive">
                            {form.errors.remark}
                        </p>
                    )}
                </div>

                <div className="mt-3">
                    <label className="mb-1 block text-xs text-muted-foreground">
                        Override reason (only if the reversal drives the balance
                        below zero)
                    </label>
                    <input
                        type="text"
                        value={form.data.override_reason}
                        onChange={(event) =>
                            form.setData('override_reason', event.target.value)
                        }
                        className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                    />
                    {form.errors.override_reason && (
                        <p className="mt-1 text-xs text-destructive">
                            {form.errors.override_reason}
                        </p>
                    )}
                </div>

                {errors.transaction && (
                    <p className="mt-3 text-xs text-destructive">
                        {errors.transaction}
                    </p>
                )}

                <div className="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-destructive px-3 py-2 text-sm font-medium text-white hover:bg-destructive/90 disabled:opacity-50"
                    >
                        {form.processing ? 'Reversing...' : 'Reverse'}
                    </button>
                </div>
            </form>
        </div>
    );
}
