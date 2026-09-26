<?php

namespace App\Http\Controllers;

use App\Models\Batch;
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
}
