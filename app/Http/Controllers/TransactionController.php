<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveActiveProfile;
use App\Models\Batch;
use App\Models\Period;
use App\Models\Profile;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Models\Ward;
use App\Services\AuditLogger;
use App\Services\BalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Records stock movements against supplier items (FR-4.1–FR-4.4).
 *
 * Every movement is a discrete, timestamped transaction attributed to the
 * acting profile. The unit cost is snapshotted from the supplier item's
 * contract price at the time of recording (FR-4.2), and the running balance is
 * always derived from the transaction log by BalanceService (FR-5.1).
 *
 * Transactions are immutable once saved: there are deliberately no update or
 * delete routes, so historical figures cannot be silently edited (FR-4.4).
 */
class TransactionController extends Controller
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

    public function __construct(
        protected AuditLogger $audit,
        protected BalanceService $balances,
    ) {}

    /**
     * Display the transaction entry screen with live balances.
     */
    public function index(): Response
    {
        $period = $this->currentPeriod();

        $supplierItems = SupplierItem::query()
            ->with(['supplier', 'item'])
            ->active()
            ->orderBy('id')
            ->get();

        return Inertia::render('Transactions', [
            'supplierItems' => $supplierItems->map(fn ($supplierItem) => [
                'id' => $supplierItem->id,
                'supplier_name' => $supplierItem->supplier?->name,
                'item_code' => $supplierItem->item?->code,
                'item_description' => $supplierItem->item?->description,
                'unit' => $supplierItem->item?->unit,
                'price' => $supplierItem->price,
                'balance' => $period
                    ? $this->balances->balanceFor($supplierItem->id, $period->id)
                    : ['quantity' => 0.0, 'total_cost' => 0.0],
            ]),
            'wards' => Ward::query()
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($ward) => [
                    'id' => $ward->id,
                    'name' => $ward->name,
                ]),
            'types' => collect(static::TYPES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'period' => $period
                ? ['year' => $period->year, 'month' => $period->month]
                : null,
            'recentTransactions' => $this->recentTransactions(),
            'canOverride' => $this->actingProfile()?->role === 'administrator',
        ]);
    }

    /**
     * Record a new stock movement.
     */
    public function store(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), $this->rules());

        $supplierItem = SupplierItem::query()->findSole($data['supplier_item_id']);

        // FR-2.4: a transaction cannot be recorded against a supplier item
        // that has no active contract price in effect.
        if (! $supplierItem->active) {
            return Redirect::back()->withErrors([
                'supplier_item_id' => 'This supplier item has no active contract price.',
            ]);
        }

        if ($supplierItem->effective_date->isFuture()) {
            return Redirect::back()->withErrors([
                'supplier_item_id' => 'This contract price is not yet in effect.',
            ]);
        }

        $date = Carbon::parse($data['transaction_date']);
        $period = Period::forDate($date);

        // FR-6.2a: a closed period is frozen against further edits.
        if ($period->status === 'closed') {
            return Redirect::back()->withErrors([
                'transaction_date' => 'The period for this date is closed and cannot accept new transactions.',
            ]);
        }

        $profile = $this->actingProfile();
        $quantity = (float) $data['quantity'];
        $unitCost = (float) $supplierItem->price;

        // FR-4.3: reject a movement that would drive the balance below zero,
        // unless an administrator explicitly overrides with a logged reason.
        $projected = $this->balances->projectedBalance(
            $supplierItem->id,
            $period->id,
            $data['type'],
            $quantity,
            $unitCost,
        );

        if ($projected['quantity'] < 0) {
            $overrideReason = trim((string) ($data['override_reason'] ?? ''));

            if ($profile?->role !== 'administrator') {
                return Redirect::back()->withErrors([
                    'quantity' => 'This would drive the balance below zero. Only an administrator can override.',
                ]);
            }

            if ($overrideReason === '') {
                return Redirect::back()->withErrors([
                    'override_reason' => 'An override reason is required to allow a negative balance.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $supplierItem, $period, $profile, $quantity, $unitCost) {
            $batchId = null;

            // FR-3.1: receiving stock requires a batch number and expiry date.
            if ($data['type'] === 'received') {
                $batch = Batch::create([
                    'supplier_item_id' => $supplierItem->id,
                    'batch_number' => $data['batch_number'],
                    'expiration_date' => $data['expiration_date'],
                    'status' => 'active',
                ]);

                $this->audit->record($batch, 'create', null, [
                    'supplier_item_id' => $batch->supplier_item_id,
                    'batch_number' => $batch->batch_number,
                    'expiration_date' => $batch->expiration_date->toDateString(),
                    'status' => $batch->status,
                ]);

                $batchId = $batch->id;
            }

            $transaction = Transaction::create([
                'period_id' => $period->id,
                'supplier_item_id' => $supplierItem->id,
                'batch_id' => $batchId,
                'type' => $data['type'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'transaction_date' => $data['transaction_date'],
                'profile_id' => $profile?->id,
                'ward_id' => $data['ward_id'] ?? null,
                'remark' => $data['remark'] ?? null,
                'override_reason' => $data['override_reason'] ?? null,
            ]);

            $this->audit->record($transaction, 'create', null, [
                'period_id' => $transaction->period_id,
                'supplier_item_id' => $transaction->supplier_item_id,
                'batch_id' => $transaction->batch_id,
                'type' => $transaction->type,
                'quantity' => $transaction->quantity,
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => $transaction->total_cost,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'ward_id' => $transaction->ward_id,
                'remark' => $transaction->remark,
                'override_reason' => $transaction->override_reason,
            ]);
        });

        return Redirect::back();
    }

    /**
     * The validation rules for recording a transaction.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'supplier_item_id' => 'required|integer|exists:supplier_items,id',
            'type' => 'required|in:'.implode(',', array_keys(static::TYPES)),
            'quantity' => 'required|numeric|gt:0',
            'transaction_date' => 'required|date',
            'ward_id' => 'nullable|integer|exists:wards,id|required_if:type,return_from_ward',
            'batch_number' => 'required_if:type,received|nullable|string|max:255',
            'expiration_date' => 'required_if:type,received|nullable|date',
            'remark' => 'required_if:type,write_off|nullable|string|max:255',
            'override_reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * The period for the current calendar month, if it exists.
     *
     * The index view is read-only, so it does not create the period; a missing
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

    /**
     * The most recently recorded transactions, for the activity list.
     *
     * @return Collection<int, array{
     *     id: int,
     *     type: string,
     *     type_label: string,
     *     supplier_name: string|null,
     *     item_code: string|null,
     *     quantity: string,
     *     total_cost: string,
     *     transaction_date: string,
     *     profile_name: string|null,
     *     ward_name: string|null,
     * }>
     */
    protected function recentTransactions()
    {
        return Transaction::query()
            ->with(['supplierItem.supplier', 'supplierItem.item', 'profile', 'ward'])
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'type_label' => $this->typeLabel($transaction->type),
                'supplier_name' => $transaction->supplierItem?->supplier?->name,
                'item_code' => $transaction->supplierItem?->item?->code,
                'quantity' => $transaction->quantity,
                'total_cost' => $transaction->total_cost,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'profile_name' => $transaction->profile?->name,
                'ward_name' => $transaction->ward?->name,
            ]);
    }

    /**
     * The display label for a movement type.
     */
    protected function typeLabel(string $type): string
    {
        return static::TYPES[$type] ?? $type;
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
