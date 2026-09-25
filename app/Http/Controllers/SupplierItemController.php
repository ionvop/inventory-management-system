<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages supplier item contract pricing (FR-2.3, FR-2.4).
 *
 * A supplier item links a supplier and an item at a specific contract price
 * with an effective date. Changing a price creates a new priced record rather
 * than overwriting history, so past transactions retain the price that was in
 * effect when they occurred (FR-2.3).
 */
class SupplierItemController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Display the supplier item catalog.
     */
    public function index(): Response
    {
        $supplierItems = SupplierItem::query()
            ->with(['supplier', 'item'])
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('SupplierItems', [
            'supplierItems' => $supplierItems->map(fn ($supplierItem) => [
                'id' => $supplierItem->id,
                'supplier_id' => $supplierItem->supplier_id,
                'supplier_name' => $supplierItem->supplier?->name,
                'item_id' => $supplierItem->item_id,
                'item_code' => $supplierItem->item?->code,
                'item_description' => $supplierItem->item?->description,
                'price' => $supplierItem->price,
                'effective_date' => $supplierItem->effective_date->toDateString(),
                'active' => $supplierItem->active,
                'has_transactions' => $supplierItem->transactions()->exists(),
            ]),
            'suppliers' => Supplier::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn ($supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                ]),
            'items' => Item::query()
                ->active()
                ->orderBy('code')
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'description' => $item->description,
                ]),
        ]);
    }

    /**
     * Create a new priced supplier item record.
     */
    public function store(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), $this->rules());

        $supplierItem = SupplierItem::create($data);

        $this->audit->record($supplierItem, 'create', null, $this->snapshot($supplierItem));

        return Redirect::back();
    }

    /**
     * Update an existing supplier item record.
     *
     * The contract price is intentionally not updatable: a price change is a
     * new record so historical transactions keep their original cost (FR-2.3).
     */
    public function update(int $id): RedirectResponse
    {
        $supplierItem = SupplierItem::query()->findSole($id);

        $before = $this->snapshot($supplierItem);

        $data = Validator::validate(Request::all(), [
            'effective_date' => [
                'required',
                'date',
                Rule::unique('supplier_items')
                    ->where('supplier_id', $supplierItem->supplier_id)
                    ->where('item_id', $supplierItem->item_id)
                    ->ignore($supplierItem->id),
            ],
            'active' => 'boolean',
        ]);

        $supplierItem->update($data);

        $this->audit->record($supplierItem, 'update', $before, $this->snapshot($supplierItem));

        return Redirect::back();
    }

    /**
     * Delete a supplier item record that has no transaction history.
     *
     * Records referenced by transactions are never deleted, so the price a
     * past transaction was recorded at remains resolvable (FR-2.3). Deactivate
     * is the normal way to retire a contract price.
     */
    public function destroy(int $id): RedirectResponse
    {
        $supplierItem = SupplierItem::query()->findSole($id);

        if ($supplierItem->transactions()->exists()) {
            return Redirect::back()->withErrors([
                'supplier_item' => 'This supplier item has recorded transactions and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $before = $this->snapshot($supplierItem);

        $supplierItem->delete();

        $this->audit->record($supplierItem, 'delete', $before, null);

        return Redirect::back();
    }

    /**
     * The validation rules for creating a priced supplier item.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'item_id' => 'required|integer|exists:items,id',
            'price' => 'required|numeric|min:0',
            'effective_date' => [
                'required',
                'date',
                Rule::unique('supplier_items')
                    ->where('supplier_id', Request::input('supplier_id'))
                    ->where('item_id', Request::input('item_id')),
            ],
            'active' => 'boolean',
        ];
    }

    /**
     * The auditable representation of a supplier item.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(SupplierItem $supplierItem): array
    {
        return [
            'supplier_id' => $supplierItem->supplier_id,
            'item_id' => $supplierItem->item_id,
            'price' => $supplierItem->price,
            'effective_date' => $supplierItem->effective_date->toDateString(),
            'active' => $supplierItem->active,
        ];
    }
}
