<?php
// app/Http/Controllers/Api/ItemController.php
namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::withStock()
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->get();

        if ($request->boolean('low_stock')) {
            $items = $items->filter(fn ($item) => $item->is_low_stock)->values();
        }

        $sort = $request->query('sort', 'name');
        $order = $request->query('order', 'asc');
        $items = $order === 'desc' ? $items->sortByDesc($sort) : $items->sortBy($sort);
        $items = $items->values();

        // Manual pagination since sorting/filtering happens in-memory
        // (current_stock is computed, not a DB column) — fine at local-inventory scale.
        $page = (int) $request->query('page', 1);
        $limit = (int) $request->query('limit', 25);
        $slice = $items->slice(($page - 1) * $limit, $limit)->values();

        return response()->json([
            'data' => $slice,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $items->count(),
                'total_pages' => (int) ceil($items->count() / $limit),
            ],
        ]);
    }

    public function store(StoreItemRequest $request)
    {
        $item = Item::create($request->validated() + ['time' => now()->timestamp]);
        return $this->data($item->loadStock(), 201);
    }

    public function show(Item $item)
    {
        return $this->data(Item::withStock()->findOrFail($item->id));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());
        return $this->data(Item::withStock()->findOrFail($item->id));
    }

    public function destroy(Item $item)
    {
        $item->delete(); // cascades transactions per the schema's ON DELETE CASCADE
        return response()->noContent();
    }

    public function lowStock(Request $request)
    {
        $request->merge(['low_stock' => 'true']);
        return $this->index($request);
    }
}