import { Form } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export interface BidRow {
    supplier_id: number;
    item_id: number;
    supplier_name: string | null;
    item_code: string | null;
    item_description: string | null;
    unit: string | null;
    has_bid: boolean;
    bid_id: number | null;
    bid_quantity: string | null;
    set_date: string | null;
    received_since: number;
    remaining: number | null;
    needs_reorder: boolean;
}

export interface BidPairOption {
    supplier_id: number;
    item_id: number;
    label: string;
}

interface BidFormProps {
    /** The pair to pre-select, when the form was opened from a row. */
    preset?: { supplier_id: number; item_id: number } | null;
    pairs: BidPairOption[];
    onSuccess?: () => void;
    onCancel?: () => void;
}

/**
 * Sets a new bid quantity for a supplier/item pair.
 *
 * A bid is a new record rather than an edit, so the history of bids is
 * preserved and the current bid is always the most recently set one.
 */
export default function BidForm({
    preset,
    pairs,
    onSuccess,
    onCancel,
}: BidFormProps) {
    const [pairKey, setPairKey] = useState(
        preset ? `${preset.supplier_id}-${preset.item_id}` : '',
    );

    // Re-select the pair when the form is opened from a different row.
    useEffect(() => {
        setPairKey(preset ? `${preset.supplier_id}-${preset.item_id}` : '');
    }, [preset]);

    const selected = pairs.find(
        (pair) => `${pair.supplier_id}-${pair.item_id}` === pairKey,
    );

    return (
        <Form action="/bids" method="post" resetOnSuccess onSuccess={onSuccess}>
            {({ errors, processing }) => (
                <div className="grid gap-3 sm:grid-cols-4">
                    <input
                        type="hidden"
                        name="supplier_id"
                        value={selected?.supplier_id ?? ''}
                    />
                    <input
                        type="hidden"
                        name="item_id"
                        value={selected?.item_id ?? ''}
                    />

                    <div className="sm:col-span-2">
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Supplier item
                        </label>
                        <select
                            value={pairKey}
                            onChange={(event) => setPairKey(event.target.value)}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        >
                            <option value="" disabled>
                                Select a supplier item
                            </option>
                            {pairs.map((pair) => (
                                <option
                                    key={`${pair.supplier_id}-${pair.item_id}`}
                                    value={`${pair.supplier_id}-${pair.item_id}`}
                                >
                                    {pair.label}
                                </option>
                            ))}
                        </select>
                        {errors.supplier_id && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.supplier_id}
                            </p>
                        )}
                        {errors.item_id && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.item_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Bid quantity
                        </label>
                        <input
                            type="number"
                            name="bid_quantity"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.bid_quantity && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.bid_quantity}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Set date
                        </label>
                        <input
                            type="date"
                            name="set_date"
                            defaultValue={new Date().toISOString().slice(0, 10)}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.set_date && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.set_date}
                            </p>
                        )}
                    </div>

                    <div className="flex items-end gap-2 sm:col-span-4">
                        <button
                            type="submit"
                            disabled={processing || !selected}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {processing ? 'Setting...' : 'Set bid quantity'}
                        </button>
                        {onCancel && (
                            <button
                                type="button"
                                onClick={onCancel}
                                className="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted"
                            >
                                Cancel
                            </button>
                        )}
                    </div>
                </div>
            )}
        </Form>
    );
}
