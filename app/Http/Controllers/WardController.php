<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the ward catalog (FR-2.1).
 *
 * Wards attribute consumption and ward-return movements (FR-4.1). A ward that
 * has recorded transactions is never deleted, so its history stays resolvable;
 * deactivating keeps it out of new transactions instead.
 */
class WardController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Display the ward catalog.
     */
    public function index(): Response
    {
        $wards = Ward::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('Wards', [
            'wards' => $wards->map(fn ($ward) => [
                'id' => $ward->id,
                'name' => $ward->name,
                'active' => $ward->active,
                'has_transactions' => $ward->transactions()->exists(),
            ]),
        ]);
    }

    /**
     * Create a new ward.
     */
    public function store(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), $this->rules());

        $ward = Ward::create($data);

        $this->audit->record($ward, 'create', null, $this->snapshot($ward));

        return Redirect::back()->with('success', 'Ward created.');
    }

    /**
     * Update an existing ward.
     */
    public function update(int $id): RedirectResponse
    {
        $ward = Ward::query()->findSole($id);

        $before = $this->snapshot($ward);

        $data = Validator::validate(Request::all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wards', 'name')->ignore($ward->id),
            ],
            'active' => 'boolean',
        ]);

        $ward->update($data);

        $this->audit->record($ward, 'update', $before, $this->snapshot($ward));

        return Redirect::back()->with('success', 'Ward updated.');
    }

    /**
     * Delete a ward that has no transaction history.
     *
     * Wards referenced by transactions are never deleted, so the ward a past
     * movement was attributed to remains resolvable (FR-4.1). Deactivate is the
     * normal way to retire a ward.
     */
    public function destroy(int $id): RedirectResponse
    {
        $ward = Ward::query()->findSole($id);

        if ($ward->transactions()->exists()) {
            return Redirect::back()->withErrors([
                'ward' => 'This ward has recorded transactions and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $before = $this->snapshot($ward);

        $ward->delete();

        $this->audit->record($ward, 'delete', $before, null);

        return Redirect::back();
    }

    /**
     * The validation rules for creating a ward.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:wards,name',
            'active' => 'boolean',
        ];
    }

    /**
     * The auditable representation of a ward.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Ward $ward): array
    {
        return [
            'name' => $ward->name,
            'active' => $ward->active,
        ];
    }
}
