<?php
// app/Models/Item.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['name', 'unit', 'minimum_stock'];
    protected $appends = ['current_stock', 'is_low_stock'];

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

    public function getCurrentStockAttribute(): int
    {
        return (int) ($this->stock_in_sum ?? 0) - (int) ($this->stock_out_sum ?? 0);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock < $this->minimum_stock;
    }

    public function loadStock(): static
    {
        return $this->loadSum(['transactions as stock_in_sum' => fn ($q) => $q->where('movement', 'in')], 'quantity')
                    ->loadSum(['transactions as stock_out_sum' => fn ($q) => $q->where('movement', 'out')], 'quantity');
    }
}