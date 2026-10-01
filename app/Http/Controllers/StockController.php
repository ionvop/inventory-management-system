<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Services\BalanceService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays a per-item stock ledger (FR-5.2).
 *
 * Shows the beginning balance carried into the open period, every movement
 * recorded in the period with the running balance after each, and the ending
 * balance. Every figure is derived from the transaction log by BalanceService
 * (FR-5.1, NFR-3.1); nothing is stored or keyed in, and the balance formula is
 * not duplicated here (NFR-5.2).
 *
 * The view is read-only and available to every role, since staff need to see
 * the running balance of the items they move (FR-5.2).
 */
class StockController extends Controller
{
    /**
     * The supported movement types and their display labels (FR-4.1).
     */
    protected const array TYPES = [
        'received' => 'Received',
        'consumption' => 'Consumption',
        'return_from_ward' => 'Return from Ward',
        'return_to_supplier' => 'Return to Supplier',
        'transfer_to_pharmacy' => 'Transfer to Pharmacy',
        'write_off' => 'Write-off (expired/damaged)',
    ];

    public function __construct(protected BalanceService $balances) {}

    /**
     * Display the stock ledger for a supplier item.
     */
    public function show(int $id): Response
    {
        $supplierItem = SupplierItem::query()
            ->with(['supplier', 'item'])
            ->findSole($id);

        $period = $this->currentPeriod();

        $beginning = $period
            ? $this->balances->beginningBalanceFor($supplierItem->id, $period->id)
            : ['quantity' => 0.0, 'total_cost' => 0.0];

        $ending = $period
            ? $this->balances->balanceFor($supplierItem->id, $period->id)
            : ['quantity' => 0.0, 'total_cost' => 0.0];

        return Inertia::render('Stock', [
            'supplierItem' => [
                'id' => $supplierItem->id,
                'supplier_name' => $supplierItem->supplier?->name,
                'item_code' => $supplierItem->item?->code,
                'item_description' => $supplierItem->item?->description,
                'unit' => $supplierItem->item?->unit,
                'price' => $supplierItem->price,
                'active' => $supplierItem->active,
            ],
            'period' => $period
                ? ['year' => $period->year, 'month' => $period->month]
                : null,
            'beginning' => $beginning,
            'ending' => $ending,
            'transactions' => $this->ledger($supplierItem->id, $period, $beginning),
        ]);
    }

    /**
     * The period's transactions with the running balance after each (FR-5.2).
     *
     * The running balance starts from the period's beginning balance and
     * applies each movement in date order, using the same sign convention as
     * BalanceService so the final row reconciles to the ending balance.
     *
     * @param  array{quantity: float, total_cost: float}  $beginning
     * @return array<int, array<string, mixed>>
     */
    protected function ledger(int $supplierItemId, ?Period $period, array $beginning): array
    {
        if (! $period instanceof Period) {
            return [];
        }

        $transactions = Transaction::query()
            ->with(['profile', 'ward'])
            ->where('supplier_item_id', $supplierItemId)
            ->where('period_id', $period->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $runningQuantity = $beginning['quantity'];
        $runningCost = $beginning['total_cost'];

        $rows = [];

        foreach ($transactions as $transaction) {
            // Decimal casts return strings in PHP, so convert before arithmetic.
            $quantity = (float) $transaction->quantity;
            $totalCost = (float) $transaction->total_cost;

            // A reversal contributes the opposite of the movement it cancels,
            // so the original and its reversal net to zero (FR-4.4).
            $sign = $this->balances->signedQuantity(
                $transaction->type,
                1.0,
                $transaction->isReversal(),
            );

            $runningQuantity += $sign * $quantity;
            $runningCost += $sign * $totalCost;

            $rows[] = [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'type' => $transaction->type,
                'type_label' => static::TYPES[$transaction->type] ?? $transaction->type,
                'quantity' => $transaction->quantity,
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => $transaction->total_cost,
                'ward_name' => $transaction->ward?->name,
                'profile_name' => $transaction->profile?->name,
                'remark' => $transaction->remark,
                'reverses_transaction_id' => $transaction->reverses_transaction_id,
                'is_reversal' => $transaction->isReversal(),
                'running_quantity' => round($runningQuantity, 2),
                'running_cost' => round($runningCost, 2),
            ];
        }

        return $rows;
    }

    /**
     * The period for the current calendar month, if it exists.
     *
     * The view is read-only, so it does not create the period; a missing
     * period simply means every balance is zero. This matches the balance
     * shown on the transaction screen, so the two views always agree.
     */
    protected function currentPeriod(): ?Period
    {
        $now = now();

        return Period::query()
            ->where('year', (int) $now->format('Y'))
            ->where('month', (int) $now->format('n'))
            ->first();
    }
}
