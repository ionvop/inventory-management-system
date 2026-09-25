<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the supplier catalog (FR-2.1).
 *
 * Suppliers are never hard-deleted: deactivating keeps them out of new
 * transactions while soft deletion preserves their history (FR-1.5 pattern).
 */
class SupplierController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Display the supplier catalog.
     */
    public function index(): Response
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('Suppliers', [
            'suppliers' => $suppliers->map(fn ($supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contract_status' => $supplier->contract_status,
                'active' => $supplier->active,
            ]),
        ]);
    }

    /**
     * Create a new supplier.
     */
    public function store(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), $this->rules());

        $supplier = Supplier::create($data);

        $this->audit->record($supplier, 'create', null, $this->snapshot($supplier));

        return Redirect::back();
    }

    /**
     * Update an existing supplier.
     */
    public function update(int $id): RedirectResponse
    {
        $supplier = Supplier::query()->findSole($id);

        $before = $this->snapshot($supplier);

        $data = Validator::validate(Request::all(), $this->rules());

        $supplier->update($data);

        $this->audit->record($supplier, 'update', $before, $this->snapshot($supplier));

        return Redirect::back();
    }

    /**
     * Soft-delete a supplier.
     */
    public function destroy(int $id): RedirectResponse
    {
        $supplier = Supplier::query()->findSole($id);

        $before = $this->snapshot($supplier);

        $supplier->delete();

        $this->audit->record($supplier, 'delete', $before, null);

        return Redirect::back();
    }

    /**
     * The validation rules shared by store and update.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contract_status' => 'nullable|string|max:255',
            'active' => 'boolean',
        ];
    }

    /**
     * The auditable representation of a supplier.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Supplier $supplier): array
    {
        return [
            'name' => $supplier->name,
            'contract_status' => $supplier->contract_status,
            'active' => $supplier->active,
        ];
    }
}
