<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Period;
use App\Models\SupplierItem;
use App\Models\Transaction;

/**
 * Builds the action-oriented dashboard payload (FR-3.3, FR-5.2, FR-6.2, FR-6.3).
 *
 * The dashboard surfaces the figures that need attention — stock to pull out,
 * balances that block a period close, overrides to review — alongside a small
 * set of at-a-glance totals and the latest movements. Every figure is derived
 * from the transaction log and batch status by the existing services
 * (NFR-3.1, NFR-5.2); nothing is stored or duplicated here.
 *
 * Alerts are scoped to the acting role: operational alerts (expiry, balances,
 * low stock) are shown to every role, while period-close and administrative
 * review alerts are limited to the roles that can act on them.
 */
class DashboardService
{
    /**
     * The movement types and their display labels (FR-4.1).
     */
    protected const array TYPE_LABELS = [
        'received' => 'Received',
        'consumption' => 'Consumption',
        'return_from_ward' => 'Return from Ward',
        'return_to_supplier' => 'Return to Supplier',
        'transfer_to_pharmacy' => 'Transfer to Pharmacy',
        'write_off' => 'Write-off (expired/damaged)',
    ];

    /**
     * The number of recent movements shown on the dashboard.
     */
    protected const int RECENT_LIMIT = 10;

    public function __construct(protected BalanceService $balances) {}

    /**
     * Build the dashboard payload for the acting role.
     *
     * @return array{
     *     alerts: array<string, int>,
     *     summary: array{
     *         period: array{year: int, month: int, status: string}|null,
     *         active_supplier_items: int,
     *         stock_value: float,
     *         transactions_this_period: int,
     *         near_expiry_days: int,
     *         low_stock_threshold: float
     *     },
     *     recentTransactions: array<int, array<string, mixed>>
     * }
     */
    public function build(string $role): array
    {
        $period = $this->currentPeriod();

        return [
            'alerts' => $this->alerts($role, $period),
            'summary' => $this->summary($period),
            'recentTransactions' => $this->recentTransactions(),
        ];
    }

    /**
     * The action-requiring counts, scoped to the acting role.
     *
     * @return array<string, int>
     */
    protected function alerts(string $role, ?Period $period): array
    {
        $alerts = [
            'expired_batches' => Batch::query()->expired()->count(),
            'near_expiry_batches' => Batch::query()->nearExpiry()->count(),
            'damaged_batches' => Batch::query()
                ->where('status', Batch::STATUS_DAMAGED)
                ->count(),
            'negative_balances' => $period instanceof Period
                ? count($this->balances->negativeBalances($period->id))
                : 0,
            'low_stock_items' => $this->lowStockItems($period),
        ];

        if (in_array($role, ['supervisor', 'administrator'], true)) {
            $alerts['open_periods_to_close'] = $this->openPeriodsToClose();
        }

        if ($role === 'administrator') {
            $alerts['override_transactions'] = $this->overrideTransactions($period);
            $alerts['missing_contract_prices'] = $this->missingContractPrices();
        }

        return $alerts;
    }

    /**
     * The at-a-glance totals for the open period.
     *
     * @return array{
     *     period: array{year: int, month: int, status: string}|null,
     *     active_supplier_items: int,
     *     stock_value: float,
     *     transactions_this_period: int,
     *     near_expiry_days: int,
     *     low_stock_threshold: float
     * }
     */
    protected function summary(?Period $period): array
    {
        $activeSupplierItems = SupplierItem::query()->active()->get();

        $stockValue = 0.0;

        if ($period instanceof Period) {
            foreach ($activeSupplierItems as $supplierItem) {
                $stockValue += $this->balances
                    ->balanceFor($supplierItem->id, $period->id)['total_cost'];
            }
        }

        return [
            'period' => $period instanceof Period
                ? [
                    'year' => $period->year,
                    'month' => $period->month,
                    'status' => $period->status,
                ]
                : null,
            'active_supplier_items' => $activeSupplierItems->count(),
            'stock_value' => round($stockValue, 2),
            'transactions_this_period' => $period instanceof Period
                ? $period->transactions()->count()
                : 0,
            'near_expiry_days' => (int) config('inventory.near_expiry_days'),
            'low_stock_threshold' => (float) config('inventory.low_stock_threshold'),
        ];
    }

    /**
     * The number of active supplier items at or below the low-stock threshold.
     *
     * Only items that have been transacted at least once are considered, so a
     * newly added catalog entry that has never been stocked is not flagged.
     */
    protected function lowStockItems(?Period $period): int
    {
        if (! $period instanceof Period) {
            return 0;
        }

        $threshold = (float) config('inventory.low_stock_threshold');

        return SupplierItem::query()
            ->active()
            ->whereHas('transactions')
            ->get()
            ->filter(function (SupplierItem $supplierItem) use ($period, $threshold): bool {
                $balance = $this->balances->balanceFor($supplierItem->id, $period->id);

                return $balance['quantity'] <= $threshold;
            })
            ->count();
    }

    /**
     * The number of open periods whose month has already ended (FR-6.2).
     */
    protected function openPeriodsToClose(): int
    {
        $now = now();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        return Period::query()
            ->where('status', 'open')
            ->where(function ($query) use ($year, $month) {
                $query->where('year', '<', $year)
                    ->orWhere(function ($query) use ($year, $month) {
                        $query->where('year', $year)->where('month', '<', $month);
                    });
            })
            ->count();
    }

    /**
     * The number of open-period overrides still standing (FR-4.3).
     *
     * A reversal cancels the movement it corrects, so an override that has
     * been reversed no longer needs review.
     */
    protected function overrideTransactions(?Period $period): int
    {
        if (! $period instanceof Period) {
            return 0;
        }

        return Transaction::query()
            ->where('period_id', $period->id)
            ->whereNotNull('override_reason')
            ->whereNull('reverses_transaction_id')
            ->whereDoesntHave('reversedBy')
            ->count();
    }

    /**
     * The number of supplier/item pairs with no contract price in effect.
     *
     * A pair without an active, in-effect price cannot be transacted against
     * (FR-2.4), so it is surfaced for an administrator to correct.
     */
    protected function missingContractPrices(): int
    {
        $pairs = SupplierItem::query()
            ->active()
            ->get()
            ->unique(fn (SupplierItem $supplierItem): string => $supplierItem->supplier_id.'-'.$supplierItem->item_id);

        return $pairs
            ->filter(fn (SupplierItem $supplierItem): bool => SupplierItem::currentFor(
                $supplierItem->supplier_id,
                $supplierItem->item_id,
            ) === null)
            ->count();
    }

    /**
     * The most recently recorded movements, for the activity list.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function recentTransactions(): array
    {
        return Transaction::query()
            ->with(['supplierItem.supplier', 'supplierItem.item', 'profile', 'ward', 'reversedBy'])
            ->orderByDesc('id')
            ->limit(static::RECENT_LIMIT)
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'type_label' => static::TYPE_LABELS[$transaction->type] ?? $transaction->type,
                'supplier_name' => $transaction->supplierItem?->supplier?->name,
                'item_code' => $transaction->supplierItem?->item?->code,
                'quantity' => $transaction->quantity,
                'total_cost' => $transaction->total_cost,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'profile_name' => $transaction->profile?->name,
                'ward_name' => $transaction->ward?->name,
                'reverses_transaction_id' => $transaction->reverses_transaction_id,
                'is_reversed' => $transaction->reversedBy !== null,
            ])
            ->all();
    }

    /**
     * The period for the current calendar month, if it exists.
     *
     * The dashboard is read-only, so it does not create the period; a missing
     * period simply means every balance is zero.
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
