<?php
// app/Models/Transaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['item_id', 'user_id', 'movement', 'quantity', 'posted_at'];
    protected $casts = ['posted_at' => 'datetime'];
    protected $appends = ['stock_after'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resulting stock for the item AFTER this transaction.
     * Populated by TransactionController::attachStockAfter() for list
     * responses; null when not computed (e.g. single-resource responses).
     */
    public function getStockAfterAttribute(): ?int
    {
        return $this->attributes['stock_after'] ?? null;
    }
}