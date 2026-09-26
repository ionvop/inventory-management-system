<?php

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_item_id
 * @property string $batch_number
 * @property Carbon $expiration_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_item_id', 'batch_number', 'expiration_date', 'status'])]
#[Hidden([])]
class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_NEAR_EXPIRY = 'near_expiry';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DAMAGED = 'damaged';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
        ];
    }

    /**
     * Store the expiration date as a plain date, without a time component.
     *
     * The default date cast serialises through the model's date format, which
     * would persist "2026-12-31 00:00:00" into a DATE column. Normalising here
     * keeps raw comparisons and assertions on the column predictable.
     */
    public function setExpirationDateAttribute(mixed $value): void
    {
        $this->attributes['expiration_date'] = $value === null
            ? null
            : Carbon::parse($value)->toDateString();
    }

    /**
     * The effective expiry status of the batch (FR-3.2).
     *
     * A batch manually flagged as damaged keeps that status. Otherwise the
     * status is derived from the expiration date and the configured near-expiry
     * threshold, so it stays correct without a scheduled job (NFR-3.1).
     */
    public function expiryStatus(): string
    {
        if ($this->status === self::STATUS_DAMAGED) {
            return self::STATUS_DAMAGED;
        }

        $days = $this->daysUntilExpiry();

        if ($days < 0) {
            return self::STATUS_EXPIRED;
        }

        if ($days <= $this->nearExpiryDays()) {
            return self::STATUS_NEAR_EXPIRY;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * The number of whole days until the batch expires (negative once past).
     */
    public function daysUntilExpiry(): int
    {
        return (int) Carbon::today()->diffInDays(
            $this->expiration_date->copy()->startOfDay(),
            false,
        );
    }

    /**
     * The configured near-expiry threshold in days (FR-3.2).
     */
    public function nearExpiryDays(): int
    {
        return (int) config('inventory.near_expiry_days');
    }

    /**
     * Scope to batches that are near expiry but not yet expired.
     *
     * @param  Builder<Batch>  $query
     */
    public function scopeNearExpiry(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_DAMAGED)
            ->whereDate('expiration_date', '>=', Carbon::today())
            ->whereDate('expiration_date', '<=', Carbon::today()->addDays($this->nearExpiryDays()));
    }

    /**
     * Scope to batches whose expiration date has passed.
     *
     * @param  Builder<Batch>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_DAMAGED)
            ->whereDate('expiration_date', '<', Carbon::today());
    }

    /**
     * Scope to batches expiring within the given number of days.
     *
     * @param  Builder<Batch>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereDate('expiration_date', '>=', Carbon::today())
            ->whereDate('expiration_date', '<=', Carbon::today()->addDays($days));
    }

    /**
     * The supplier item this batch belongs to.
     *
     * @return BelongsTo<SupplierItem, $this>
     */
    public function supplierItem(): BelongsTo
    {
        return $this->belongsTo(SupplierItem::class);
    }
}
