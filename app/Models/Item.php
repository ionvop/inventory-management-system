<?php
// app/Models/Item.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'minimum_stock'];
    protected $appends = ['current_stock', 'is_low_stock', 'current_bid'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Attach computed stock-in/out sums. Call explicitly wherever
     * current_stock / is_low_stock need to be accurate — they are
     * NOT computed unless this scope was applied to the query.
     */
    public function scopeWithStock($query)
    {
        return $query
            ->withSum(['transactions as stock_in_sum' => fn ($q) => $q->where('movement', 'in')], 'quantity')
            ->withSum(['transactions as stock_out_sum' => fn ($q) => $q->where('movement', 'out')], 'quantity');
    }

    /**
     * Load the item's full transaction history (ordered) so the computed
     * current_bid accessor can be derived. Call explicitly wherever
     * current_bid needs to be accurate — it is NOT computed unless this
     * scope (or loadBid) was applied.
     */
    public function scopeWithBid($query)
    {
        return $query->with(['transactions' => fn ($q) => $q->orderBy('posted_at')->orderBy('id')]);
    }

    public function getCurrentStockAttribute(): int
    {
        return (int) ($this->stock_in_sum ?? 0) - (int) ($this->stock_out_sum ?? 0);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock < $this->minimum_stock;
    }

    /**
     * Current bid quantity for the item, derived from its ordered history:
     * a "bid" transaction sets the bid, and every subsequent "in" deducts
     * from it (clamped at 0). "out" transactions leave the bid unchanged.
     * Returns 0 when the history has not been loaded.
     */
    public function getCurrentBidAttribute(): int
    {
        if (! $this->relationLoaded('transactions')) {
            return 0;
        }

        $bid = 0;
        foreach ($this->transactions as $tx) {
            if ($tx->movement === 'bid') {
                $bid = $tx->quantity;
            } elseif ($tx->movement === 'in') {
                $bid = max(0, $bid - $tx->quantity);
            }
        }

        return $bid;
    }

    public function loadStock(): static
    {
        return $this->loadSum(['transactions as stock_in_sum' => fn ($q) => $q->where('movement', 'in')], 'quantity')
                    ->loadSum(['transactions as stock_out_sum' => fn ($q) => $q->where('movement', 'out')], 'quantity')
                    ->loadBid();
    }

    public function loadBid(): static
    {
        return $this->load(['transactions' => fn ($q) => $q->orderBy('posted_at')->orderBy('id')]);
    }
}