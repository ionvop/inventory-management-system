import { Link } from '@inertiajs/react';
import { useState } from 'react';
import BidForm, {
    type BidPairOption,
    type BidRow,
} from '@/components/bid-form';
import AppLayout from '@/layouts/app-layout';

interface BidsProps {
    rows: BidRow[];
    reorderThreshold: number;
}

export default function Bids({ rows, reorderThreshold }: BidsProps) {
    const [preset, setPreset] = useState<{
        supplier_id: number;
        item_id: number;
    } | null>(null);

    const pairs: BidPairOption[] = rows.map((row) => ({
        supplier_id: row.supplier_id,
        item_id: row.item_id,
        label: `${row.supplier_name ?? 'Unknown supplier'} — ${row.item_code ?? 'Item'}${
            row.item_description ? ` (${row.item_description})` : ''
        }`,
    }));

    const reorderCount = rows.filter(
        (row) => row.has_bid && row.needs_reorder,
    ).length;

    return (
        <AppLayout title="Bids">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">
                    Bid quantities
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    The quantity committed to a supplier for an item, typically
                    set once every six months. Stock received against the pair
                    deducts from the bid, so the remaining quantity shows how
                    much is still outstanding. When it drops to{' '}
                    {reorderThreshold.toLocaleString()} or below, it is time to
                    place a new bid.
                </p>
                {reorderCount > 0 && (
                    <p className="mt-2 text-sm font-medium text-amber-600 dark:text-amber-400">
                        {reorderCount} bid{reorderCount === 1 ? '' : 's'} at or
                        below the reorder threshold.
                    </p>
                )}
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Supplier</th>
                            <th className="px-4 py-3 font-medium">Item</th>
                            <th className="px-4 py-3 text-right font-medium">
                                Bid quantity
                            </th>
                            <th className="px-4 py-3 font-medium">Set date</th>
                            <th className="px-4 py-3 text-right font-medium">
                                Received since
                            </th>
                            <th className="px-4 py-3 text-right font-medium">
                                Remaining
                            </th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No transactable supplier items yet. Add a
                                    contract price first.
                                </td>
                            </tr>
                        )}
                        {rows.map((row) => (
                            <tr
                                key={`${row.supplier_id}-${row.item_id}`}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {row.supplier_name ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {row.item_code ?? '—'}
                                    {row.item_description
                                        ? ` — ${row.item_description}`
                                        : ''}
                                </td>
                                <td className="px-4 py-3 text-right text-foreground">
                                    {row.has_bid
                                        ? row.bid_quantity
                                        : 'No bid set'}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {row.set_date ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-right text-muted-foreground">
                                    {row.has_bid ? row.received_since : '—'}
                                </td>
                                <td
                                    className={
                                        row.needs_reorder
                                            ? 'px-4 py-3 text-right font-medium text-amber-600 dark:text-amber-400'
                                            : 'px-4 py-3 text-right font-medium text-foreground'
                                    }
                                >
                                    {row.remaining ?? '—'}
                                </td>
                                <td className="px-4 py-3">
                                    {!row.has_bid ? (
                                        <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                            No bid
                                        </span>
                                    ) : row.needs_reorder ? (
                                        <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                                            Reorder
                                        </span>
                                    ) : (
                                        <span className="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                            On track
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setPreset({
                                                    supplier_id:
                                                        row.supplier_id,
                                                    item_id: row.item_id,
                                                })
                                            }
                                            className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                        >
                                            {row.has_bid
                                                ? 'Set new bid'
                                                : 'Set bid'}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-8 rounded-lg border border-border bg-card p-4">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-sm font-semibold text-foreground">
                        Set a bid quantity
                    </h2>
                    {preset && (
                        <button
                            type="button"
                            onClick={() => setPreset(null)}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            Clear selection
                        </button>
                    )}
                </div>
                {pairs.length > 0 ? (
                    <BidForm
                        preset={preset}
                        pairs={pairs}
                        onSuccess={() => setPreset(null)}
                    />
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Add a contract price on the{' '}
                        <Link
                            href="/supplier-items"
                            className="text-primary hover:underline"
                        >
                            Supplier Items
                        </Link>{' '}
                        screen before setting a bid.
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
