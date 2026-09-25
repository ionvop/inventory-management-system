<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the item catalog (FR-2.2).
 *
 * Items are never hard-deleted: deactivating keeps them out of new
 * transactions while soft deletion preserves their history (FR-1.5 pattern).
 */
class ItemController extends Controller
{
    /**
     * Display the item catalog.
     */
    public function index(): Response
    {
        $items = Item::query()
            ->orderBy('code')
            ->get();

        return Inertia::render('Items', [
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'description' => $item->description,
                'unit' => $item->unit,
                'active' => $item->active,
            ]),
        ]);
    }

    /**
     * Create a new item.
     */
    public function store(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), $this->rules());

        Item::create($data);

        return Redirect::back();
    }

    /**
     * Update an existing item.
     */
    public function update(int $id): RedirectResponse
    {
        $item = Item::query()->findSole($id);

        $data = Validator::validate(Request::all(), $this->rules());

        $item->update($data);

        return Redirect::back();
    }

    /**
     * Soft-delete an item.
     */
    public function destroy(int $id): RedirectResponse
    {
        $item = Item::query()->findSole($id);

        $item->delete();

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
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'active' => 'boolean',
        ];
    }
}
