<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $period_id
 * @property int $supplier_item_id
 * @property int|null $batch_id
 * @property string $type
 * @property string $quantity
 * @property string $unit_cost
 * @property string $total_cost
 * @property Carbon $transaction_date
 * @property int $profile_id
 * @property int|null $ward_id
 * @property string|null $remark
 * @property string|null $override_reason
 * @property int|null $reverses_transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'period_id',
    'supplier_item_id',
    'batch_id',
    'type',
    'quantity',
    'unit_cost',
    'total_cost',
    'transaction_date',
    'profile_id',
    'ward_id',
    'remark',
    'override_reason',
    'reverses_transaction_id',
])]
#[Hidden([])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /**
     * Store the transaction date as a plain date, without a time component.
     *
     * The default date cast serialises through the model's date format, which
     * would persist "2026-01-05 00:00:00" into a DATE column. Normalising here
     * keeps raw comparisons and assertions on the column predictable.
     */
    public function setTransactionDateAttribute(mixed $value): void
    {
        $this->attributes['transaction_date'] = $value === null
            ? null
            : Carbon::parse($value)->toDateString();
    }

    /**
     * The supplier item this transaction moves stock for.
     *
     * @return BelongsTo<SupplierItem, $this>
     */
    public function supplierItem(): BelongsTo
    {
        return $this->belongsTo(SupplierItem::class);
    }

    /**
     * The profile that recorded this transaction.
     *
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * The batch this transaction moved stock from, when applicable.
     *
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * The ward this transaction is attributed to, when applicable.
     *
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
