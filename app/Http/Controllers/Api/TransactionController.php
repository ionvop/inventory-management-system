<?php
// app/Http/Controllers/Api/TransactionController.php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['item:id,name', 'user:id,username'])
            ->when($request->query('item_id'), fn ($q, $v) => $q->where('item_id', $v))
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('movement'), fn ($q, $v) => $q->where('movement', $v))
            ->when($request->query('date_from'), fn ($q, $v) => $q->where('posted_at', '>=', $v))
            ->when($request->query('date_to'), fn ($q, $v) => $q->where('posted_at', '<=', $v))
            ->orderBy($request->query('sort', 'posted_at'), $request->query('order', 'desc'));

        return $this->paginated($query->paginate($request->query('limit', 25)));
    }

    public function byItem(Request $request, Item $item)
    {
        $request->merge(['item_id' => $item->id]);
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
            'posted_at' => $validated['posted_at'] ?? now()->startOfDay(),
        ]);

        return $this->data($transaction->load(['item:id,name', 'user:id,username']), 201);
    }

    public function show(Transaction $transaction)
    {
        return $this->data($transaction->load(['item:id,name', 'user:id,username']));
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
        return $this->data($transaction->fresh(['item:id,name', 'user:id,username']));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->noContent();
    }
}