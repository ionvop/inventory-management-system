<?php

namespace App\Services;

use App\Models\Period;
use App\Models\PeriodBalance;
use App\Models\Transaction;

/**
 * Computes running stock balances from the transaction log.
 *
 * Balances are always derived at read time from recorded transactions
 * (NFR-3.1) and never stored as an independently editable field. This is the
 * single source of truth for the balance formula (FR-5.1) and must not be
 * duplicated in the frontend (NFR-5.2).
 *
 * A period's balance starts from the beginning balance carried forward from
 * the previous period's close snapshot (FR-6.2c), then applies the movements
 * recorded within the period.
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
     * The balance begins with the amount carried forward from the previous
     * period's close snapshot (FR-6.2c), then applies every movement recorded
     * within the period (FR-5.1).
     *
     * @return array{quantity: float, total_cost: float}
     */
    public function balanceFor(int $supplierItemId, int $periodId): array
    {
        $beginning = $this->beginningBalanceFor($supplierItemId, $periodId);

        $transactions = Transaction::query()
            ->where('supplier_item_id', $supplierItemId)
            ->where('period_id', $periodId)
            ->get();

        $runningQuantity = $beginning['quantity'];
        $runningCost = $beginning['total_cost'];

        foreach ($transactions as $transaction) {
            // Decimal casts return strings in PHP, so convert before arithmetic.
            $quantity = (float) $transaction->quantity;
            $totalCost = (float) $transaction->total_cost;

            if (in_array($transaction->type, static::INCREASING_TYPES, true)) {
                $runningQuantity += $quantity;
                $runningCost += $totalCost;
            } elseif (in_array($transaction->type, static::DECREASING_TYPES, true)) {
                $runningQuantity -= $quantity;
                $runningCost -= $totalCost;
            }
        }

        return [
            'quantity' => round($runningQuantity, 2),
            'total_cost' => round($runningCost, 2),
        ];
    }

    /**
     * The beginning balance carried forward into a period (FR-6.2c).
     *
     * This is the previous period's ending snapshot for the supplier item. A
     * period with no preceding snapshot starts from zero, so the first live
     * period behaves exactly as before.
     *
     * @return array{quantity: float, total_cost: float}
     */
    public function beginningBalanceFor(int $supplierItemId, int $periodId): array
    {
        $period = Period::query()->find($periodId);

        if (! $period instanceof Period) {
            return ['quantity' => 0.0, 'total_cost' => 0.0];
        }

        $previous = $period->previous();

        if (! $previous instanceof Period) {
            return ['quantity' => 0.0, 'total_cost' => 0.0];
        }

        $snapshot = PeriodBalance::query()
            ->where('period_id', $previous->id)
            ->where('supplier_item_id', $supplierItemId)
            ->first();

        if (! $snapshot instanceof PeriodBalance) {
            return ['quantity' => 0.0, 'total_cost' => 0.0];
        }

        return [
            'quantity' => round((float) $snapshot->ending_quantity, 2),
            'total_cost' => round((float) $snapshot->ending_cost, 2),
        ];
    }

    /**
     * The signed quantity a movement type contributes to a balance.
     *
     * Increasing types add, decreasing types subtract (FR-5.1). This is the
     * single place the sign convention lives, so callers never re-implement
     * the formula (NFR-5.2).
     */
    public function signedQuantity(string $type, float $quantity): float
    {
        if (in_array($type, static::INCREASING_TYPES, true)) {
            return $quantity;
        }

        if (in_array($type, static::DECREASING_TYPES, true)) {
            return -$quantity;
        }

        return 0.0;
    }

    /**
     * Compute the balance a supplier item would have after a proposed movement.
     *
     * Used to reject a transaction that would drive the running quantity below
     * zero (FR-4.3) before it is persisted.
     *
     * @return array{quantity: float, total_cost: float}
     */
    public function projectedBalance(
        int $supplierItemId,
        int $periodId,
        string $type,
        float $quantity,
        float $unitCost,
    ): array {
        $balance = $this->balanceFor($supplierItemId, $periodId);

        $sign = $this->signedQuantity($type, 1.0);

        return [
            'quantity' => round($balance['quantity'] + ($sign * $quantity), 2),
            'total_cost' => round($balance['total_cost'] + ($sign * $quantity * $unitCost), 2),
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
