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
 * @property float $quantity
 * @property float $unit_cost
 * @property float $total_cost
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
}
