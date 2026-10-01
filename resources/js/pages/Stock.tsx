import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';

interface SupplierItemSummary {
    id: number;
    supplier_name: string | null;
    item_code: string | null;
    item_description: string | null;
    unit: string | null;
    price: string;
    active: boolean;
}

interface LedgerRow {
    id: number;
    transaction_date: string;
    type: string;
    type_label: string;
    quantity: string;
    unit_cost: string;
    total_cost: string;
    ward_name: string | null;
    profile_name: string | null;
    remark: string | null;
    reverses_transaction_id: number | null;
    is_reversal: boolean;
    running_quantity: number;
    running_cost: number;
}

interface Balance {
    quantity: number;
    total_cost: number;
}

interface StockProps {
    supplierItem: SupplierItemSummary;
    period: { year: number; month: number } | null;
    beginning: Balance;
    ending: Balance;
    transactions: LedgerRow[];
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

export default function Stock({
    supplierItem,
    period,
    beginning,
    ending,
    transactions,
}: StockProps) {
    const periodLabel = period
        ? `${monthNames[period.month - 1]} ${period.year}`
        : 'No period yet';

    return (
        <AppLayout title="Stock">
            <div className="mb-6">
                <Link
                    href="/transactions"
                    className="mb-3 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="h-3 w-3" aria-hidden="true" />
                    Back to transactions
                </Link>
                <h1 className="text-xl font-bold text-foreground">
                    {supplierItem.item_code ?? 'Item'}
                    {supplierItem.item_description
                        ? ` — ${supplierItem.item_description}`
                        : ''}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {supplierItem.supplier_name ?? 'Unknown supplier'}
                    {supplierItem.unit ? ` · ${supplierItem.unit}` : ''}
                    {` · Contract price ${supplierItem.price}`}
                    {!supplierItem.active ? ' · Inactive' : ''}
                </p>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground uppercase">
                        Beginning ({periodLabel})
                    </p>
                    <p className="mt-1 text-2xl font-bold text-foreground">
                        {beginning.quantity}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {beginning.total_cost} cost
                    </p>
                </div>
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground uppercase">
                        Ending ({periodLabel})
                    </p>
                    <p
                        className={
                            ending.quantity < 0
                                ? 'mt-1 text-2xl font-bold text-destructive'
                                : 'mt-1 text-2xl font-bold text-foreground'
                        }
                    >
                        {ending.quantity}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {ending.total_cost} cost
                    </p>
                </div>
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground uppercase">
                        Movements this period
                    </p>
                    <p className="mt-1 text-2xl font-bold text-foreground">
                        {transactions.length}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Derived from the transaction log
                    </p>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Date</th>
                            <th className="px-4 py-3 font-medium">Type</th>
                            <th className="px-4 py-3 font-medium">Qty</th>
                            <th className="px-4 py-3 font-medium">
                                Unit cost
                            </th>
                            <th className="px-4 py-3 font-medium">
                                Total cost
                            </th>
                            <th className="px-4 py-3 font-medium">Ward</th>
                            <th className="px-4 py-3 font-medium">
                                Recorded by
                            </th>
                            <th className="px-4 py-3 text-right font-medium">
                                Running qty
                            </th>
                            <th className="px-4 py-3 text-right font-medium">
                                Running cost
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="border-b border-border bg-muted/30">
                            <td
                                colSpan={7}
                                className="px-4 py-3 text-xs font-medium text-muted-foreground uppercase"
                            >
                                Beginning balance
                            </td>
                            <td className="px-4 py-3 text-right font-medium text-foreground">
                                {beginning.quantity}
                            </td>
                            <td className="px-4 py-3 text-right font-medium text-foreground">
                                {beginning.total_cost}
                            </td>
                        </tr>
                        {transactions.length === 0 && (
                            <tr>
                                <td
                                    colSpan={9}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No movements recorded in {periodLabel}.
                                </td>
                            </tr>
                        )}
                        {transactions.map((transaction) => (
                            <tr
                                key={transaction.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 text-muted-foreground">
                                    {transaction.transaction_date}
                                </td>
                                <td className="px-4 py-3 text-foreground">
                                    {transaction.type_label}
                                    {transaction.is_reversal && (
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            Reversal
                                        </span>
                                    )}
                                    {transaction.remark && (
                                        <span className="block text-xs text-muted-foreground">
                                            {transaction.remark}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-foreground">
                                    {transaction.quantity}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {transaction.unit_cost}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {transaction.total_cost}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {transaction.ward_name ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {transaction.profile_name ?? '—'}
                                </td>
                                <td
                                    className={
                                        transaction.running_quantity < 0
                                            ? 'px-4 py-3 text-right font-medium text-destructive'
                                            : 'px-4 py-3 text-right font-medium text-foreground'
                                    }
                                >
                                    {transaction.running_quantity}
                                </td>
                                <td className="px-4 py-3 text-right text-foreground">
                                    {transaction.running_cost}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
