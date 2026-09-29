<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the near-expiry / expired batch dashboard (FR-3.3).
 *
 * Batch expiry status is derived at read time from the expiration date and the
 * configured near-expiry threshold (FR-3.2), so no scheduled job is required.
 * A batch manually flagged as damaged keeps that status regardless of its date.
 */
class BatchController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Display batches grouped by their effective expiry status.
     */
    public function index(): Response
    {
        $batches = Batch::query()
            ->with(['supplierItem.supplier', 'supplierItem.item'])
            ->orderBy('expiration_date')
            ->get();

        $rows = $batches->map(fn (Batch $batch) => [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'expiration_date' => $batch->expiration_date->toDateString(),
            'days_until_expiry' => $batch->daysUntilExpiry(),
            'status' => $batch->expiryStatus(),
            'supplier_name' => $batch->supplierItem?->supplier?->name,
            'item_code' => $batch->supplierItem?->item?->code,
            'item_description' => $batch->supplierItem?->item?->description,
            'unit' => $batch->supplierItem?->item?->unit,
            'damaged_reason' => $batch->damaged_reason,
        ]);

        return Inertia::render('Batches', [
            'batches' => $rows,
            'counts' => [
                'expired' => $rows->where('status', Batch::STATUS_EXPIRED)->count(),
                'near_expiry' => $rows->where('status', Batch::STATUS_NEAR_EXPIRY)->count(),
                'active' => $rows->where('status', Batch::STATUS_ACTIVE)->count(),
                'damaged' => $rows->where('status', Batch::STATUS_DAMAGED)->count(),
            ],
            'nearExpiryDays' => (int) config('inventory.near_expiry_days'),
        ]);
    }

    /**
     * Flag a batch as damaged for pull-out (FR-3.2).
     *
     * A damaged batch keeps that status regardless of its expiration date, so
     * it is surfaced on the dashboard and in the report remarks. The reason is
     * required and the action is attributed to the acting profile (NFR-2.2).
     * Any role may flag a batch, since staff perform the pull-outs (FR-3.3).
     */
    public function flagDamaged(int $id): RedirectResponse
    {
        $batch = Batch::query()->findSole($id);

        if ($batch->status === Batch::STATUS_DAMAGED) {
            return Redirect::back()->withErrors([
                'batch' => 'This batch is already flagged as damaged.',
            ]);
        }

        $data = Validator::validate(Request::all(), [
            'damaged_reason' => 'required|string|max:255',
        ]);

        $before = [
            'status' => $batch->status,
            'damaged_reason' => $batch->damaged_reason,
        ];

        $batch->update([
            'status' => Batch::STATUS_DAMAGED,
            'damaged_reason' => $data['damaged_reason'],
        ]);

        $this->audit->record($batch, 'damage', $before, [
            'status' => $batch->status,
            'damaged_reason' => $batch->damaged_reason,
        ]);

        return Redirect::back();
    }
}
