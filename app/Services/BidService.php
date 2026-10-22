<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\SupplierItem;
use App\Models\Transaction;

/**
 * Computes bid quantities and how much of each bid remains outstanding.
 *
 * A bid is the quantity the department has committed to buy from a supplier
 * for an item, typically set once every six months. Stock received against the
 * pair deducts from the bid, so the remaining quantity shows how much of the
 * commitment is still outstanding and signals when it is time to place another
 * bid.
 *
 * Like every other derived figure in the system (NFR-3.1, NFR-5.2), the
 * remaining quantity is computed at read time from the transaction log and is
 * never stored. This is the single source of truth for the bid formula and must
 * not be duplicated in the frontend.
 *
 * Bid quantities are for internal tracking only and are never included in the
 * exported report documents.
 */
class BidService
{
    /**
     * The net quantity received against a bid since it was set.
     *
     * Only receipts count against a bid, and only those dated on or after the
     * bid's set date, so a bid set today is not reduced by stock that arrived
     * before it. A reversal of a receipt adds the quantity back, but is
     * attributed to the ORIGINAL receipt's date: a reversal is dated today, so
     * attributing it to today would wrongly credit a bid that started after the
     * receipt it cancels.
     */
    public function receivedSince(Bid $bid): float
    {
        $setDate = $bid->set_date->toDateString();

        $transactions = Transaction::query()
            ->where('type', 'received')
            ->whereHas('supplierItem', function ($query) use ($bid) {
                $query->where('supplier_id', $bid->supplier_id)
                    ->where('item_id', $bid->item_id);
            })
            ->with('reversesTransaction')
            ->get();

        $received = 0.0;

        foreach ($transactions as $transaction) {
            // A reversal is dated today, so the date that matters is the date
            // of the receipt it cancels.
            $effectiveDate = $transaction->isReversal()
                ? $transaction->reversesTransaction?->transaction_date?->toDateString()
                : $transaction->transaction_date->toDateString();

            if ($effectiveDate === null || $effectiveDate < $setDate) {
                continue;
            }

            $quantity = (float) $transaction->quantity;

            $received += $transaction->isReversal() ? -$quantity : $quantity;
        }

        return round($received, 2);
    }

    /**
     * The quantity still outstanding against a bid.
     *
     * This is the bid quantity less everything received since it was set. It
     * can go negative when more stock arrives than was bid, which is a signal
     * the bid was under-estimated rather than an error.
     */
    public function remaining(Bid $bid): float
    {
        return round((float) $bid->bid_quantity - $this->receivedSince($bid), 2);
    }

    /**
     * Whether a bid has dropped to or below the reorder threshold.
     *
     * The threshold is a global, configurable quantity (config/inventory.php),
     * so the department can tune when an item is flagged for a new bid without
     * a code change.
     */
    public function needsReorder(Bid $bid): bool
    {
        return $this->remaining($bid) <= (float) config('inventory.bid_reorder_threshold');
    }

    /**
     * The number of current bids at or below the reorder threshold.
     *
     * Used by the dashboard alert. Only pairs with a current bid are counted,
     * since a pair that has never been bid on has nothing to reorder.
     */
    public function reorderCount(): int
    {
        return count(array_filter(
            $this->rows(),
            fn (array $row): bool => $row['has_bid'] && $row['needs_reorder'],
        ));
    }

    /**
     * Build the bid tracking rows for every transactable supplier/item pair.
     *
     * A pair is transactable when it has an active contract price (FR-2.4), so
     * the list matches the pairs a transaction can be recorded against. Pairs
     * that have never been bid on are included with a null bid so a supervisor
     * can set one.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        $pairs = SupplierItem::query()
            ->with(['supplier', 'item'])
            ->active()
            ->orderBy('id')
            ->get()
            ->unique(fn (SupplierItem $supplierItem): string => $supplierItem->supplier_id.'-'.$supplierItem->item_id);

        return $pairs
            ->map(function (SupplierItem $supplierItem): array {
                $bid = Bid::currentFor($supplierItem->supplier_id, $supplierItem->item_id);

                $received = $bid instanceof Bid ? $this->receivedSince($bid) : 0.0;
                $remaining = $bid instanceof Bid ? $this->remaining($bid) : null;

                return [
                    'supplier_id' => $supplierItem->supplier_id,
                    'item_id' => $supplierItem->item_id,
                    'supplier_name' => $supplierItem->supplier?->name,
                    'item_code' => $supplierItem->item?->code,
                    'item_description' => $supplierItem->item?->description,
                    'unit' => $supplierItem->item?->unit,
                    'has_bid' => $bid instanceof Bid,
                    'bid_id' => $bid?->id,
                    'bid_quantity' => $bid?->bid_quantity,
                    'set_date' => $bid?->set_date->toDateString(),
                    'received_since' => $received,
                    'remaining' => $remaining,
                    'needs_reorder' => $bid instanceof Bid && $this->needsReorder($bid),
                ];
            })
            ->values()
            ->all();
    }
}
