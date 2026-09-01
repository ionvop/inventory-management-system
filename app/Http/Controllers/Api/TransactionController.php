<?php
// app/Http/Controllers/Api/TransactionController.php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Item;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['item:id,name,unit', 'user:id,username'])
            ->when($request->query('item_id'), fn ($q, $v) => $q->where('item_id', $v))
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('movement'), fn ($q, $v) => $q->where('movement', $v))
            ->when($request->query('date_from'), fn ($q, $v) => $q->where('posted_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($request->query('date_to'), fn ($q, $v) => $q->where('posted_at', '<=', Carbon::parse($v)->endOfDay()))
            ->orderBy($request->query('sort', 'posted_at'), $request->query('order', 'desc'));

        $transactions = $query->paginate($request->query('limit', 25));
        $this->attachStockAfter($transactions->items());

        return $this->paginated($transactions);
    }

    /**
     * Attach a per-item running balance ("stock_after") to each transaction
     * on the current page. Computed from the FULL history of each item on the
     * page (ordered by posted_at, then id) so pagination and movement/date
     * filters don't skew the resulting stock.
     */
    private function attachStockAfter(array $transactions): void
    {
        $itemIds = collect($transactions)
            ->pluck('item_id')
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return;
        }

        $history = Transaction::whereIn('item_id', $itemIds)
            ->orderBy('item_id')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get(['id', 'item_id', 'movement', 'quantity']);

        $running = [];
        $stockAfterById = [];

        foreach ($history as $tx) {
            $delta = $tx->movement === 'in' ? $tx->quantity : -$tx->quantity;
            $running[$tx->item_id] = ($running[$tx->item_id] ?? 0) + $delta;
            $stockAfterById[$tx->item_id][$tx->id] = $running[$tx->item_id];
        }

        foreach ($transactions as $tx) {
            $tx->stock_after = $stockAfterById[$tx->item_id][$tx->id] ?? null;
        }
    }

    public function byItem(Request $request, Item $item)
    {
        // Put item_id into the query bag so index()'s $request->query('item_id') filter works.
        $request->query->set('item_id', $item->id);
        return $this->index($request);
    }

    public function store(StoreTransactionRequest $request)
    {
        $validated = $request->validated();
        $item = Item::withStock()->findOrFail($validated['item_id']);

        if ($validated['movement'] === 'out' && $validated['quantity'] > $item->current_stock) {
            throw new ApiException('INSUFFICIENT_STOCK', 'Not enough stock for this transaction.', 422, [
                'current_stock' => $item->current_stock,
                'requested' => $validated['quantity'],
            ]);
        }

        $transaction = Transaction::create($validated + [
            'user_id' => $request->user()->id,
            'posted_at' => $validated['posted_at'] ?? now(),
        ]);

        return $this->data($transaction->load(['item:id,name,unit', 'user:id,username']), 201);
    }

    public function show(Transaction $transaction)
    {
        return $this->data($transaction->load(['item:id,name,unit', 'user:id,username']));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $validated = $request->validated();
        $movement = $validated['movement'] ?? $transaction->movement;
        $quantity = $validated['quantity'] ?? $transaction->quantity;

        $item = Item::withStock()->findOrFail($transaction->item_id);
        // Back out this transaction's existing effect before re-checking.
        $stockExcludingThis = $item->current_stock - ($transaction->movement === 'in' ? $transaction->quantity : -$transaction->quantity);

        if ($movement === 'out' && $quantity > $stockExcludingThis) {
            throw new ApiException('INSUFFICIENT_STOCK', 'This edit would drive stock negative.', 422, [
                'current_stock_excluding_this_transaction' => $stockExcludingThis,
                'requested' => $quantity,
            ]);
        }

        $transaction->update($validated);
        return $this->data($transaction->fresh(['item:id,name,unit', 'user:id,username']));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->noContent();
    }
}