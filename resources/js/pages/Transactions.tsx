import { Link } from '@inertiajs/react';
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
}

interface TransactionsProps {
    supplierItems: SupplierItemOption[];
    wards: WardOption[];
    types: TransactionTypeOption[];
    period: { year: number; month: number } | null;
    recentTransactions: RecentTransaction[];
    canOverride: boolean;
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
}: TransactionsProps) {
    const periodLabel = period
        ? `${monthNames[period.month - 1]} ${period.year}`
        : 'No period yet';

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
                                    {supplierItem.item_code ?? '—'}
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
            </div>
        </AppLayout>
    );
}
