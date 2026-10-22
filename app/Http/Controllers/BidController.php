<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveActiveProfile;
use App\Models\Bid;
use App\Models\Profile;
use App\Services\AuditLogger;
use App\Services\BidService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tracks bid quantities for supplier/item pairs.
 *
 * A bid is the quantity the department has committed to buy from a supplier
 * for an item, typically set once every six months. Stock received against the
 * pair deducts from the bid, so the remaining quantity signals when it is time
 * to place another bid. Setting a bid is a supervisor/administrator activity,
 * matching period closing and reporting.
 *
 * Bid quantities are for internal tracking only and are never included in the
 * exported report documents.
 */
class BidController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected BidService $bids,
    ) {}

    /**
     * Display the bid tracking screen.
     */
    public function index(): Response
    {
        return Inertia::render('Bids', [
            'rows' => $this->bids->rows(),
            'reorderThreshold' => (float) config('inventory.bid_reorder_threshold'),
        ]);
    }

    /**
     * Set a new bid quantity for a supplier/item pair.
     *
     * Each bid is a new record rather than an edit, so the history of bids is
     * preserved and the current bid is always the most recently set one.
     */
    public function store(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'item_id' => 'required|integer|exists:items,id',
            'bid_quantity' => 'required|numeric|gt:0',
            'set_date' => 'required|date',
        ]);

        $profile = $this->actingProfile();

        $bid = Bid::create([
            'supplier_id' => $data['supplier_id'],
            'item_id' => $data['item_id'],
            'bid_quantity' => $data['bid_quantity'],
            'set_date' => $data['set_date'],
            'profile_id' => $profile?->id,
        ]);

        $this->audit->record($bid, 'create', null, $this->snapshot($bid));

        return Redirect::back()->with('success', 'Bid quantity set.');
    }

    /**
     * The auditable representation of a bid.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Bid $bid): array
    {
        return [
            'supplier_id' => $bid->supplier_id,
            'item_id' => $bid->item_id,
            'bid_quantity' => $bid->bid_quantity,
            'set_date' => $bid->set_date->toDateString(),
            'profile_id' => $bid->profile_id,
        ];
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
