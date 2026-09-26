<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveActiveProfile;
use App\Models\Period;
use App\Models\PeriodBalance;
use App\Models\Profile;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Services\AuditLogger;
use App\Services\BalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages monthly period closing and carry-forward (FR-6.2–FR-6.4).
 *
 * Closing a period freezes it against further transactions, snapshots each
 * supplier item's ending balance, and makes that snapshot the next period's
 * beginning balance. A period cannot be closed while any item shows a negative
 * balance (FR-6.3), and only an administrator may reopen a closed period, with
 * a logged reason (FR-6.4).
 */
class PeriodController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected BalanceService $balances,
    ) {}

    /**
     * Display the monthly periods and their close status.
     */
    public function index(): Response
    {
        $periods = Period::query()
            ->with('balances')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return Inertia::render('Periods', [
            'periods' => $periods->map(fn (Period $period) => [
                'id' => $period->id,
                'year' => $period->year,
                'month' => $period->month,
                'status' => $period->status,
                'closed_at' => $period->closed_at?->toDateTimeString(),
                'closed_by' => $this->closerName($period),
                'negative_count' => count($this->balances->negativeBalances($period->id)),
                'transaction_count' => $period->transactions()->count(),
            ]),
            'canReopen' => $this->actingProfile()?->role === 'administrator',
        ]);
    }

    /**
     * Close a period, snapshotting each item's ending balance (FR-6.2).
     */
    public function close(int $id): RedirectResponse
    {
        $period = Period::query()->findSole($id);

        if ($period->status === 'closed') {
            return Redirect::back()->withErrors([
                'period' => 'This period is already closed.',
            ]);
        }

        // FR-6.3: a period with a negative computed balance cannot be closed
        // until the offending rows are corrected.
        $negatives = $this->balances->negativeBalances($period->id);

        if ($negatives !== []) {
            return Redirect::back()->withErrors([
                'period' => $this->negativeBalanceMessage($negatives),
            ]);
        }

        $profile = $this->actingProfile();

        DB::transaction(function () use ($period, $profile) {
            $this->snapshotBalances($period);

            $period->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $profile?->id,
                'reopened_reason' => null,
            ]);

            $this->audit->record($period, 'close', null, [
                'year' => $period->year,
                'month' => $period->month,
                'status' => $period->status,
                'closed_at' => $period->closed_at?->toDateTimeString(),
                'closed_by' => $period->closed_by,
            ]);
        });

        return Redirect::back();
    }

    /**
     * Reopen a closed period, requiring a logged reason (FR-6.4).
     */
    public function reopen(int $id): RedirectResponse
    {
        $period = Period::query()->findSole($id);

        if ($period->status !== 'closed') {
            return Redirect::back()->withErrors([
                'period' => 'This period is not closed.',
            ]);
        }

        $data = Validator::validate(Request::all(), [
            'reopened_reason' => 'required|string|max:255',
        ]);

        $before = [
            'status' => $period->status,
            'closed_at' => $period->closed_at?->toDateTimeString(),
            'closed_by' => $period->closed_by,
        ];

        $period->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
            'reopened_reason' => $data['reopened_reason'],
        ]);

        $this->audit->record($period, 'reopen', $before, [
            'status' => $period->status,
            'reopened_reason' => $period->reopened_reason,
        ]);

        return Redirect::back();
    }

    /**
     * Snapshot each supplier item's beginning and ending balance for a period.
     *
     * Only items with activity in the period or a non-zero carried-forward
     * beginning balance are recorded, so the snapshot stays focused on items
     * that actually moved (FR-6.2b).
     */
    protected function snapshotBalances(Period $period): void
    {
        foreach ($this->snapshotSupplierItemIds($period) as $supplierItemId) {
            $beginning = $this->balances->beginningBalanceFor($supplierItemId, $period->id);
            $ending = $this->balances->balanceFor($supplierItemId, $period->id);

            PeriodBalance::updateOrCreate(
                [
                    'period_id' => $period->id,
                    'supplier_item_id' => $supplierItemId,
                ],
                [
                    'beginning_quantity' => $beginning['quantity'],
                    'beginning_cost' => $beginning['total_cost'],
                    'ending_quantity' => $ending['quantity'],
                    'ending_cost' => $ending['total_cost'],
                ],
            );
        }
    }

    /**
     * The supplier items to snapshot for a period.
     *
     * @return array<int, int>
     */
    protected function snapshotSupplierItemIds(Period $period): array
    {
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
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Build the error message listing items blocking a close (FR-6.3).
     *
     * @param  array<int, array{quantity: float, total_cost: float}>  $negatives
     */
    protected function negativeBalanceMessage(array $negatives): string
    {
        $labels = SupplierItem::query()
            ->with(['supplier', 'item'])
            ->whereIn('id', array_keys($negatives))
            ->get()
            ->map(fn (SupplierItem $supplierItem) => sprintf(
                '%s — %s (%s)',
                $supplierItem->supplier->name,
                $supplierItem->item->code,
                $negatives[$supplierItem->id]['quantity'],
            ))
            ->all();

        return 'This period cannot be closed while items show a negative balance: '
            .implode('; ', $labels).'. Correct these transactions first.';
    }

    /**
     * The name of the profile that closed a period, if any.
     */
    protected function closerName(Period $period): ?string
    {
        if ($period->closed_by === null) {
            return null;
        }

        return Profile::withTrashed()->find($period->closed_by)?->name;
    }

    /**
     * The profile performing the current request, if one is selected.
     */
    protected function actingProfile(): ?Profile
    {
        $profile = request()->attributes->get(ResolveActiveProfile::ATTRIBUTE);

        return $profile instanceof Profile ? $profile : null;
    }
}
