<?php

namespace App\Services;

use App\Models\Period;
use App\Models\PeriodBalance;
use App\Models\SupplierItem;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Builds the department's monthly stock report (FR-7.1).
 *
 * The report is grouped by supplier and shows, for each supplier item, the
 * beginning balance, a column per movement type, and the ending balance, each
 * with quantity and total-cost figures, plus per-supplier subtotals and a grand
 * subtotal.
 *
 * All figures are derived from the transaction log (NFR-3.1). For a closed
 * period the beginning and ending balances are read from the frozen
 * period_balances snapshot, so regenerating the report always reproduces the
 * same numbers (FR-7.4). For an open period they are computed live by
 * BalanceService. The movement columns are always aggregated from the
 * transactions themselves, which are immutable once recorded (FR-4.4).
 */
class ReportService
{
    /**
     * The movement columns, in report order (FR-7.1).
     *
     * Write-off is included alongside the columns named in the proposal so the
     * row reconciles: Beginning + Received + Return from Ward − Return to
     * Supplier − Transfer to Pharmacy − Consumption − Write-off = Ending
     * (FR-5.1).
     */
    public const array MOVEMENT_TYPES = [
        'received',
        'return_from_ward',
        'return_to_supplier',
        'transfer_to_pharmacy',
        'consumption',
        'write_off',
    ];

    public function __construct(protected BalanceService $balances) {}

    /**
     * Build the full report payload for a period.
     *
     * @return array{
     *     period: array{id: int, year: int, month: int, status: string},
     *     groups: array<int, array{
     *         supplier_id: int|null,
     *         supplier_name: string|null,
     *         rows: array<int, array<string, mixed>>,
     *         subtotal: array<string, array{quantity: float, total_cost: float}>
     *     }>,
     *     grand_total: array<string, array{quantity: float, total_cost: float}>
     * }
     */
    public function build(Period $period): array
    {
        $rows = collect($this->rows($period));

        $groups = $rows
            ->groupBy('supplier_id')
            ->map(function (Collection $group): array {
                /** @var array<string, mixed> $first */
                $first = $group->first();

                return [
                    'supplier_id' => $first['supplier_id'],
                    'supplier_name' => $first['supplier_name'],
                    'rows' => $group->values()->all(),
                    'subtotal' => $this->totals($group),
                ];
            })
            ->values()
            ->all();

        return [
            'period' => [
                'id' => $period->id,
                'year' => $period->year,
                'month' => $period->month,
                'status' => $period->status,
            ],
            'groups' => $groups,
            'grand_total' => $this->totals($rows),
        ];
    }

    /**
     * Build one report row per supplier item with activity or a carried balance.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rows(Period $period): array
    {
        $supplierItems = SupplierItem::query()
            ->with(['supplier', 'item'])
            ->whereIn('id', $this->supplierItemIds($period))
            ->get()
            ->sortBy(fn (SupplierItem $supplierItem): string => sprintf(
                '%s|%s',
                $supplierItem->supplier?->name,
                $supplierItem->item?->code,
            ))
            ->values();

        $transactions = Transaction::query()
            ->with('batch')
            ->where('period_id', $period->id)
            ->get()
            ->groupBy('supplier_item_id');

        $snapshots = $period->status === 'closed'
            ? PeriodBalance::query()
                ->where('period_id', $period->id)
                ->get()
                ->keyBy('supplier_item_id')
            : collect();

        return $supplierItems->map(function (SupplierItem $supplierItem) use ($period, $transactions, $snapshots): array {
            /** @var Collection<int, Transaction> $itemTransactions */
            $itemTransactions = $transactions->get($supplierItem->id, collect());

            $movements = [];

            foreach (self::MOVEMENT_TYPES as $type) {
                $movements[$type] = $this->movementTotals($itemTransactions, $type);
            }

            $batches = $itemTransactions
                ->map(fn (Transaction $transaction) => $transaction->batch)
                ->filter()
                ->unique('id')
                ->sortBy('expiration_date')
                ->values();

            return array_merge([
                'supplier_item_id' => $supplierItem->id,
                'supplier_id' => $supplierItem->supplier_id,
                'supplier_name' => $supplierItem->supplier?->name,
                'item_code' => $supplierItem->item?->code,
                'item_description' => $supplierItem->item?->description,
                'unit' => $supplierItem->item?->unit,
                'batch_numbers' => $batches->pluck('batch_number')->implode(', ') ?: null,
                'expiration_dates' => $batches
                    ->map(fn ($batch) => $batch->expiration_date->toDateString())
                    ->implode(', ') ?: null,
                'contract_price' => $supplierItem->price,
                'beginning' => $this->beginningFor($period, $supplierItem->id, $snapshots),
            ], $movements, [
                'ending' => $this->endingFor($period, $supplierItem->id, $snapshots),
            ]);
        })->values()->all();
    }

    /**
     * The supplier items to include in the report.
     *
     * A closed period uses its frozen snapshot, which already lists exactly the
     * items that moved or carried a balance. An open period includes items with
     * activity in the period plus items carrying a non-zero balance forward.
     *
     * @return array<int, int>
     */
    protected function supplierItemIds(Period $period): array
    {
        if ($period->status === 'closed') {
            return PeriodBalance::query()
                ->where('period_id', $period->id)
                ->pluck('supplier_item_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $withActivity = Transaction::query()
            ->where('period_id', $period->id)
            ->distinct()
            ->pluck('supplier_item_id');

        $previous = $period->previous();

        $carriedForward = $previous instanceof Period
            ? PeriodBalance::query()
                ->where('period_id', $previous->id)
                ->where(function ($query) {
                    $query->where('ending_quantity', '!=', 0)
                        ->orWhere('ending_cost', '!=', 0);
                })
                ->pluck('supplier_item_id')
            : collect();

        return $withActivity
            ->merge($carriedForward)
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * The beginning balance for a supplier item (FR-6.2c).
     *
     * A closed period reads the frozen snapshot; an open period derives it from
     * the previous period's close.
     *
     * @param  Collection<int, PeriodBalance>  $snapshots
     * @return array{quantity: float, total_cost: float}
     */
    protected function beginningFor(Period $period, int $supplierItemId, Collection $snapshots): array
    {
        $snapshot = $snapshots->get($supplierItemId);

        if ($snapshot instanceof PeriodBalance) {
            return [
                'quantity' => round((float) $snapshot->beginning_quantity, 2),
                'total_cost' => round((float) $snapshot->beginning_cost, 2),
            ];
        }

        return $this->balances->beginningBalanceFor($supplierItemId, $period->id);
    }

    /**
     * The ending balance for a supplier item.
     *
     * A closed period reads the frozen snapshot (FR-7.4); an open period is
     * computed live from the transaction log (FR-5.2).
     *
     * @param  Collection<int, PeriodBalance>  $snapshots
     * @return array{quantity: float, total_cost: float}
     */
    protected function endingFor(Period $period, int $supplierItemId, Collection $snapshots): array
    {
        $snapshot = $snapshots->get($supplierItemId);

        if ($snapshot instanceof PeriodBalance) {
            return [
                'quantity' => round((float) $snapshot->ending_quantity, 2),
                'total_cost' => round((float) $snapshot->ending_cost, 2),
            ];
        }

        return $this->balances->balanceFor($supplierItemId, $period->id);
    }

    /**
     * Sum one movement type's quantity and cost for a supplier item.
     *
     * Each column is shown in the movement's natural direction, matching the
     * department's existing report: a consumption of 3 reads as 3, not -3. The
     * ending balance is derived independently by BalanceService, so the sign
     * convention lives in one place (NFR-5.2). A reversal contributes the
     * opposite of the movement it cancels, so an original and its reversal net
     * to zero within the same column (FR-4.4).
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array{quantity: float, total_cost: float}
     */
    protected function movementTotals(Collection $transactions, string $type): array
    {
        $quantity = 0.0;
        $cost = 0.0;

        foreach ($transactions as $transaction) {
            if ($transaction->type !== $type) {
                continue;
            }

            $sign = $transaction->isReversal() ? -1.0 : 1.0;

            $quantity += $sign * (float) $transaction->quantity;
            $cost += $sign * (float) $transaction->total_cost;
        }

        return [
            'quantity' => round($quantity, 2),
            'total_cost' => round($cost, 2),
        ];
    }

    /**
     * Sum every column across a set of report rows.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, array{quantity: float, total_cost: float}>
     */
    protected function totals(Collection $rows): array
    {
        $columns = array_merge(['beginning'], self::MOVEMENT_TYPES, ['ending']);

        $totals = [];

        foreach ($columns as $column) {
            $totals[$column] = [
                'quantity' => round($rows->sum(fn ($row): float => (float) $row[$column]['quantity']), 2),
                'total_cost' => round($rows->sum(fn ($row): float => (float) $row[$column]['total_cost']), 2),
            ];
        }

        return $totals;
    }
}
