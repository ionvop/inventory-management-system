<?php

namespace App\Services;

use App\Models\Transaction;

/**
 * Computes running stock balances from the transaction log.
 *
 * Balances are always derived at read time from recorded transactions
 * (NFR-3.1) and never stored as an independently editable field. This is the
 * single source of truth for the balance formula (FR-5.1) and must not be
 * duplicated in the frontend (NFR-5.2).
 */
class BalanceService
{
    /**
     * The movement types that increase an item's balance.
     */
    protected const array INCREASING_TYPES = ['received', 'return_from_ward'];

    /**
     * The movement types that decrease an item's balance.
     */
    protected const array DECREASING_TYPES = [
        'return_to_supplier',
        'transfer_to_pharmacy',
        'consumption',
        'write_off',
    ];

    /**
     * Compute the running balance for a supplier item within a period.
     *
     * @return array{quantity: float, total_cost: float}
     */
    public function balanceFor(int $supplierItemId, int $periodId): array
    {
        $transactions = Transaction::query()
            ->where('supplier_item_id', $supplierItemId)
            ->where('period_id', $periodId)
            ->get();

        $quantity = 0.0;
        $totalCost = 0.0;

        foreach ($transactions as $transaction) {
            if (in_array($transaction->type, static::INCREASING_TYPES)) {
                $quantity += $transaction->quantity;
                $totalCost += $transaction->total_cost;
            } elseif (in_array($transaction->type, static::DECREASING_TYPES)) {
                $quantity -= $transaction->quantity;
                $totalCost -= $transaction->total_cost;
            }
        }

        return [
            'quantity' => round($quantity, 2),
            'total_cost' => round($totalCost, 2),
        ];
    }

    /**
     * List supplier items whose computed balance is negative within a period.
     *
     * @return array<int, array{quantity: float, total_cost: float}>
     */
    public function negativeBalances(int $periodId): array
    {
        $supplierItemIds = Transaction::query()
            ->where('period_id', $periodId)
            ->distinct()
            ->pluck('supplier_item_id');

        $negatives = [];

        foreach ($supplierItemIds as $supplierItemId) {
            $balance = $this->balanceFor($supplierItemId, $periodId);

            if ($balance['quantity'] < 0) {
                $negatives[$supplierItemId] = $balance;
            }
        }

        return $negatives;
    }
}
