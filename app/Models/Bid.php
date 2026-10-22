<?php

namespace App\Models;

use Database\Factories\BidFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A bid quantity set for a supplier/item pair.
 *
 * A bid is the quantity the department has committed to buy from a supplier
 * for an item (e.g. 10,000 cans), typically set once every six months. Stock
 * received against the pair deducts from the bid, so the remaining quantity
 * shows how much of the commitment is still outstanding and signals when it is
 * time to place another bid.
 *
 * Bids are keyed by the supplier/item pair rather than a single supplier_items
 * row, because a price change creates a new priced record (FR-2.3) and the bid
 * must outlive it. Each bid is preserved as history; the current bid is the one
 * with the greatest set date.
 *
 * Bid quantities are for internal tracking only and are never included in the
 * exported report documents.
 *
 * @property int $id
 * @property int $supplier_id
 * @property int $item_id
 * @property string $bid_quantity
 * @property Carbon $set_date
 * @property int|null $profile_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_id', 'item_id', 'bid_quantity', 'set_date', 'profile_id'])]
#[Hidden([])]
class Bid extends Model
{
    /** @use HasFactory<BidFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bid_quantity' => 'decimal:2',
            'set_date' => 'date',
        ];
    }

    /**
     * Store the set date as a plain date, without a time component.
     *
     * The default date cast serialises through the model's date format, which
     * would persist "2026-01-01 00:00:00" into a DATE column. Normalising here
     * keeps raw comparisons and assertions on the column predictable.
     */
    public function setSetDateAttribute(mixed $value): void
    {
        $this->attributes['set_date'] = $value === null
            ? null
            : Carbon::parse($value)->toDateString();
    }

    /**
     * The supplier this bid is placed with.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * The item this bid covers.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The profile that set this bid.
     *
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Resolve the current bid for a supplier/item pair.
     *
     * The current bid is the one with the greatest set date, tie-broken by the
     * most recently created record. Returns null when the pair has never been
     * bid on.
     */
    public static function currentFor(int $supplierId, int $itemId): ?self
    {
        return static::query()
            ->where('supplier_id', $supplierId)
            ->where('item_id', $itemId)
            ->orderByDesc('set_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Limit the query to bids for a supplier/item pair.
     *
     * @param  Builder<Bid>  $query
     */
    public function scopeForPair(Builder $query, int $supplierId, int $itemId): void
    {
        $query->where('supplier_id', $supplierId)->where('item_id', $itemId);
    }
}
