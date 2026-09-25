<?php

namespace App\Models;

use Database\Factories\SupplierItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_id
 * @property int $item_id
 * @property float $price
 * @property Carbon $effective_date
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_id', 'item_id', 'price', 'effective_date', 'active'])]
#[Hidden([])]
class SupplierItem extends Model
{
    /** @use HasFactory<SupplierItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'effective_date' => 'date',
            'active' => 'boolean',
        ];
    }

    /**
     * Store the effective date as a plain date, without a time component.
     *
     * The default date cast serialises through the model's date format, which
     * would persist "2026-01-01 00:00:00" into a DATE column. That trailing
     * time makes the (supplier_id, item_id, effective_date) uniqueness check
     * miss an existing row, so the value is normalised here.
     */
    public function setEffectiveDateAttribute(mixed $value): void
    {
        $this->attributes['effective_date'] = $value === null
            ? null
            : Carbon::parse($value)->toDateString();
    }

    /**
     * The transactions recorded against this supplier item.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The supplier offering this item.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * The item being offered.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Limit the query to supplier items that are currently active.
     *
     * @param  Builder<SupplierItem>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Resolve the contract price in effect for a supplier/item pair.
     *
     * Price changes create a new priced record rather than overwriting history
     * (FR-2.3), so the price in effect is the active record with the greatest
     * effective date that is not in the future. Returns null when the pair has
     * no active contract price, which is what blocks a transaction from being
     * recorded against it (FR-2.4).
     */
    public static function currentFor(int $supplierId, int $itemId): ?self
    {
        return static::query()
            ->where('supplier_id', $supplierId)
            ->where('item_id', $itemId)
            ->active()
            ->where('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();
    }
}
